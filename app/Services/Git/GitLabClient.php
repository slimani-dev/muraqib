<?php

namespace App\Services\Git;

class GitLabClient extends GitClient
{
    public function checkConnection(): bool
    {
        return $this->connects(filled($this->account->token) ? '/user' : '/projects?per_page=1');
    }

    public function repository(string $owner, string $name): array
    {
        $path = '/projects/'.rawurlencode("{$owner}/{$name}");
        $project = $this->http()->get($path)->json();

        $mergeRequests = collect($this->http()->get("{$path}/merge_requests", ['state' => 'opened', 'per_page' => 100])->json());
        $issues = $this->optional(fn () => $this->http()->get("{$path}/issues", ['state' => 'opened', 'per_page' => 20])->json());
        $release = $this->optional(fn () => $this->http()->get("{$path}/releases", ['per_page' => 1])->json('0'));
        $pipeline = $this->optional(fn () => $this->http()->get("{$path}/pipelines", ['per_page' => 1, 'ref' => $project['default_branch'] ?? null])->json('0'));
        $languages = $this->optional(fn () => $this->http()->get("{$path}/languages")->json());

        return [
            'description' => $project['description'] ?? null,
            'html_url' => $project['web_url'] ?? null,
            'default_branch' => $project['default_branch'] ?? null,
            'language' => $languages ? array_key_first($languages) : null,
            'stars' => $project['star_count'] ?? null,
            'forks' => $project['forks_count'] ?? null,
            'open_issues' => $project['open_issues_count'] ?? null,
            'open_pull_requests' => $mergeRequests->count(),
            'pushed_at' => $project['last_activity_at'] ?? null,
            'latest_release' => $release ? [
                'tag' => $release['tag_name'],
                'name' => $release['name'] ?? null,
                'url' => $release['_links']['self'] ?? null,
                'published_at' => $release['released_at'] ?? null,
            ] : null,
            'ci' => $pipeline ? [
                'status' => $this->ciStatus($pipeline['status'] ?? null),
                'url' => $pipeline['web_url'] ?? null,
                'finished_at' => $pipeline['updated_at'] ?? null,
            ] : null,
            'pull_requests' => $mergeRequests->take(20)->map(fn (array $mr): array => [
                'number' => $mr['iid'],
                'title' => $mr['title'],
                'url' => $mr['web_url'] ?? null,
                'author' => $mr['author']['username'] ?? null,
                'created_at' => $mr['created_at'] ?? null,
                'draft' => (bool) ($mr['draft'] ?? $mr['work_in_progress'] ?? false),
                'reviewers' => count($mr['reviewers'] ?? []),
            ])->values()->all(),
            'issues' => $issues === null ? null : collect($issues)->map(fn (array $issue): array => [
                'number' => $issue['iid'],
                'title' => $issue['title'],
                'url' => $issue['web_url'] ?? null,
                'author' => $issue['author']['username'] ?? null,
                'created_at' => $issue['created_at'] ?? null,
                'comments' => $issue['user_notes_count'] ?? 0,
            ])->values()->all(),
        ];
    }

    protected function apiUrl(): string
    {
        return $this->account->webUrl().'/api/v4';
    }

    protected function headers(): array
    {
        return array_filter(['PRIVATE-TOKEN' => $this->account->token]);
    }
}
