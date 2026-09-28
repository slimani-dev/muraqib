<?php

namespace App\Services\Git;

class GitHubClient extends GitClient
{
    public function checkConnection(): bool
    {
        return $this->connects(filled($this->account->token) ? '/user' : '/rate_limit');
    }

    public function repository(string $owner, string $name): array
    {
        $path = '/repos/'.rawurlencode($owner).'/'.rawurlencode($name);
        $repo = $this->http()->get($path)->json();

        $pulls = collect($this->http()->get("{$path}/pulls", ['state' => 'open', 'per_page' => 100])->json());
        $issues = collect($this->http()->get("{$path}/issues", ['state' => 'open', 'per_page' => 30])->json())
            ->reject(fn (array $issue): bool => isset($issue['pull_request']));
        $release = $this->optional(fn () => $this->http()->get("{$path}/releases/latest")->json());
        $run = $this->optional(fn () => $this->http()->get("{$path}/actions/runs", ['per_page' => 1, 'branch' => $repo['default_branch'] ?? null])->json('workflow_runs.0'));

        return [
            'description' => $repo['description'] ?? null,
            'html_url' => $repo['html_url'] ?? null,
            'default_branch' => $repo['default_branch'] ?? null,
            'language' => $repo['language'] ?? null,
            'stars' => $repo['stargazers_count'] ?? null,
            'forks' => $repo['forks_count'] ?? null,
            // GitHub counts open PRs as issues too
            'open_issues' => max(0, ($repo['open_issues_count'] ?? 0) - $pulls->count()),
            'open_pull_requests' => $pulls->count(),
            'pushed_at' => $repo['pushed_at'] ?? null,
            'latest_release' => $release ? [
                'tag' => $release['tag_name'],
                'name' => $release['name'] ?? null,
                'url' => $release['html_url'] ?? null,
                'published_at' => $release['published_at'] ?? null,
            ] : null,
            'ci' => $run ? [
                'status' => $this->ciStatus($run['status'] === 'completed' ? $run['conclusion'] : $run['status']),
                'url' => $run['html_url'] ?? null,
                'finished_at' => $run['updated_at'] ?? null,
            ] : null,
            'pull_requests' => $pulls->take(20)->map(fn (array $pr): array => [
                'number' => $pr['number'],
                'title' => $pr['title'],
                'url' => $pr['html_url'] ?? null,
                'author' => $pr['user']['login'] ?? null,
                'created_at' => $pr['created_at'] ?? null,
                'draft' => (bool) ($pr['draft'] ?? false),
                'reviewers' => count($pr['requested_reviewers'] ?? []),
            ])->values()->all(),
            'issues' => $issues->take(20)->map(fn (array $issue): array => [
                'number' => $issue['number'],
                'title' => $issue['title'],
                'url' => $issue['html_url'] ?? null,
                'author' => $issue['user']['login'] ?? null,
                'created_at' => $issue['created_at'] ?? null,
                'comments' => $issue['comments'] ?? 0,
            ])->values()->all(),
        ];
    }

    /**
     * The contribution calendar shown on the GitHub profile, via GraphQL. Needs a token.
     */
    public function contributions(): ?array
    {
        if (blank($this->account->token)) {
            return null;
        }

        $query = <<<'GRAPHQL'
            query {
              viewer {
                login
                contributionsCollection {
                  totalCommitContributions
                  totalPullRequestContributions
                  totalPullRequestReviewContributions
                  totalIssueContributions
                  restrictedContributionsCount
                  contributionCalendar {
                    totalContributions
                    weeks { contributionDays { date contributionCount contributionLevel } }
                  }
                }
              }
            }
            GRAPHQL;

        $viewer = $this->http()->post($this->graphQlUrl(), ['query' => $query])->json('data.viewer');

        if (! is_array($viewer)) {
            return null;
        }

        $collection = $viewer['contributionsCollection'];
        $levels = ['NONE' => 0, 'FIRST_QUARTILE' => 1, 'SECOND_QUARTILE' => 2, 'THIRD_QUARTILE' => 3, 'FOURTH_QUARTILE' => 4];

        return [
            'username' => $viewer['login'],
            'total' => $collection['contributionCalendar']['totalContributions'],
            'commits' => $collection['totalCommitContributions'],
            'pull_requests' => $collection['totalPullRequestContributions'],
            'reviews' => $collection['totalPullRequestReviewContributions'],
            'issues' => $collection['totalIssueContributions'],
            'private' => $collection['restrictedContributionsCount'],
            'weeks' => collect($collection['contributionCalendar']['weeks'])->map(fn (array $week): array => collect($week['contributionDays'])->map(fn (array $day): array => [
                'date' => $day['date'],
                'count' => $day['contributionCount'],
                'level' => $levels[$day['contributionLevel']] ?? 0,
            ])->all())->all(),
        ];
    }

    /**
     * The notification inbox: unread and read threads that aren't marked as done, newest first.
     *
     * @return list<array{id: string, unread: bool, reason: string, updated_at: string, title: string, type: string, number: ?string, repository: string, repository_avatar: ?string, url: string}>
     */
    public function notifications(): array
    {
        return collect($this->http()->get('/notifications', ['all' => 'true', 'per_page' => 50])->json())
            ->map(fn (array $thread): array => [
                'id' => (string) $thread['id'],
                'unread' => (bool) $thread['unread'],
                'reason' => $thread['reason'],
                'updated_at' => $thread['updated_at'],
                'title' => $thread['subject']['title'],
                'type' => $thread['subject']['type'],
                'number' => $this->subjectNumber($thread['subject']['url'] ?? null),
                'repository' => $thread['repository']['full_name'],
                'repository_avatar' => $thread['repository']['owner']['avatar_url'] ?? null,
                'url' => $this->subjectWebUrl($thread),
            ])
            ->all();
    }

    public function markNotificationRead(string $threadId): void
    {
        $this->http()->patch("/notifications/threads/{$threadId}");
    }

    public function markNotificationDone(string $threadId): void
    {
        $this->http()->delete("/notifications/threads/{$threadId}");
    }

    /**
     * Stop notifications for this thread (like GitHub's Unsubscribe button).
     */
    public function unsubscribeFromNotification(string $threadId): void
    {
        $this->http()->put("/notifications/threads/{$threadId}/subscription", ['ignored' => true]);
    }

    private function subjectNumber(?string $apiUrl): ?string
    {
        return $apiUrl && preg_match('#/(issues|pulls|discussions)/(\d+)$#', $apiUrl, $matches) ? $matches[2] : null;
    }

    /**
     * The page to open for a notification. The API only gives API URLs, so map them to the website.
     *
     * @param  array<string, mixed>  $thread
     */
    private function subjectWebUrl(array $thread): string
    {
        $repositoryUrl = $thread['repository']['html_url'];
        $apiUrl = $thread['subject']['url'] ?? null;

        if ($apiUrl && preg_match('#/repos/[^/]+/[^/]+/(issues|pulls|discussions|commits)/([^/]+)$#', $apiUrl, $matches)) {
            $path = ['pulls' => 'pull', 'commits' => 'commit'][$matches[1]] ?? $matches[1];

            return "{$repositoryUrl}/{$path}/{$matches[2]}";
        }

        return match ($thread['subject']['type']) {
            'Release' => "{$repositoryUrl}/releases",
            'CheckSuite', 'WorkflowRun' => "{$repositoryUrl}/actions",
            default => $repositoryUrl,
        };
    }

    private function graphQlUrl(): string
    {
        $web = $this->account->webUrl();

        return $web === 'https://github.com' ? 'https://api.github.com/graphql' : "{$web}/api/graphql";
    }

    protected function apiUrl(): string
    {
        $web = $this->account->webUrl();

        return $web === 'https://github.com' ? 'https://api.github.com' : "{$web}/api/v3";
    }

    protected function headers(): array
    {
        return array_filter([
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
            'Authorization' => filled($this->account->token) ? "Bearer {$this->account->token}" : null,
        ]);
    }
}
