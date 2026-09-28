<?php

namespace App\Services\Git;

/**
 * Gitea and its forks, Forgejo and Codeberg, share the same API.
 */
class GiteaClient extends GitClient
{
    public function checkConnection(): bool
    {
        return $this->connects(filled($this->account->token) ? '/user' : '/version');
    }

    public function repository(string $owner, string $name): array
    {
        $path = '/repos/'.rawurlencode($owner).'/'.rawurlencode($name);
        $repo = $this->http()->get($path)->json();

        $pulls = collect($this->http()->get("{$path}/pulls", ['state' => 'open', 'limit' => 50])->json());
        $issues = $this->optional(fn () => $this->http()->get("{$path}/issues", ['state' => 'open', 'type' => 'issues', 'limit' => 20])->json());
        $release = $this->optional(fn () => $this->http()->get("{$path}/releases/latest")->json());
        $run = $this->optional(fn () => $this->http()->get("{$path}/actions/tasks", ['limit' => 1])->json('workflow_runs.0'));

        return [
            'description' => $repo['description'] ?? null,
            'html_url' => $repo['html_url'] ?? null,
            'default_branch' => $repo['default_branch'] ?? null,
            'language' => $repo['language'] ?? null,
            'stars' => $repo['stars_count'] ?? null,
            'forks' => $repo['forks_count'] ?? null,
            'open_issues' => $repo['open_issues_count'] ?? null,
            'open_pull_requests' => $repo['open_pr_counter'] ?? $pulls->count(),
            'pushed_at' => $repo['updated_at'] ?? null,
            'latest_release' => $release ? [
                'tag' => $release['tag_name'],
                'name' => $release['name'] ?? null,
                'url' => $release['html_url'] ?? null,
                'published_at' => $release['published_at'] ?? null,
            ] : null,
            'ci' => $run ? [
                'status' => $this->ciStatus($run['status'] ?? null),
                'url' => isset($run['url']) ? $run['url'] : null,
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
            'issues' => $issues === null ? null : collect($issues)->map(fn (array $issue): array => [
                'number' => $issue['number'],
                'title' => $issue['title'],
                'url' => $issue['html_url'] ?? null,
                'author' => $issue['user']['login'] ?? null,
                'created_at' => $issue['created_at'] ?? null,
                'comments' => $issue['comments'] ?? 0,
            ])->values()->all(),
        ];
    }

    protected function apiUrl(): string
    {
        return $this->account->webUrl().'/api/v1';
    }

    protected function headers(): array
    {
        return array_filter(['Authorization' => filled($this->account->token) ? "token {$this->account->token}" : null]);
    }
}
