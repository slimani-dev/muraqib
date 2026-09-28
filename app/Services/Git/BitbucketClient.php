<?php

namespace App\Services\Git;

use Illuminate\Http\Client\PendingRequest;

/**
 * Bitbucket Cloud. It has no stars; releases are read from the newest tag.
 */
class BitbucketClient extends GitClient
{
    public function checkConnection(): bool
    {
        return $this->connects('/user');
    }

    public function repository(string $owner, string $name): array
    {
        $path = '/repositories/'.rawurlencode($owner).'/'.rawurlencode($name);
        $repo = $this->http()->get($path)->json();
        $htmlUrl = $repo['links']['html']['href'] ?? null;

        $pulls = $this->http()->get("{$path}/pullrequests", ['state' => 'OPEN', 'pagelen' => 50])->json();
        $forks = $this->optional(fn () => $this->http()->get("{$path}/forks", ['pagelen' => 1])->json('size'));
        $issues = $this->optional(fn () => $this->http()->get("{$path}/issues", ['q' => 'state="new" OR state="open"', 'pagelen' => 20])->json());
        $tag = $this->optional(fn () => $this->http()->get("{$path}/refs/tags", ['sort' => '-target.date', 'pagelen' => 1])->json('values.0'));
        $pipeline = $this->optional(fn () => $this->http()->get("{$path}/pipelines/", ['sort' => '-created_on', 'pagelen' => 1])->json('values.0'));

        return [
            'description' => ($repo['description'] ?? null) ?: null,
            'html_url' => $htmlUrl,
            'default_branch' => $repo['mainbranch']['name'] ?? null,
            'language' => ($repo['language'] ?? null) ?: null,
            'stars' => null,
            'forks' => $forks,
            'open_issues' => $issues['size'] ?? null,
            'open_pull_requests' => $pulls['size'] ?? count($pulls['values'] ?? []),
            'pushed_at' => $repo['updated_on'] ?? null,
            'latest_release' => $tag ? [
                'tag' => $tag['name'],
                'name' => null,
                'url' => $tag['links']['html']['href'] ?? null,
                'published_at' => $tag['target']['date'] ?? null,
            ] : null,
            'ci' => $pipeline ? [
                'status' => $this->ciStatus($pipeline['state']['result']['name'] ?? $pipeline['state']['name'] ?? null),
                'url' => $htmlUrl && isset($pipeline['build_number']) ? "{$htmlUrl}/pipelines/results/{$pipeline['build_number']}" : null,
                'finished_at' => $pipeline['completed_on'] ?? $pipeline['created_on'] ?? null,
            ] : null,
            'pull_requests' => collect($pulls['values'] ?? [])->take(20)->map(fn (array $pr): array => [
                'number' => $pr['id'],
                'title' => $pr['title'],
                'url' => $pr['links']['html']['href'] ?? null,
                'author' => $pr['author']['display_name'] ?? null,
                'created_at' => $pr['created_on'] ?? null,
                'draft' => (bool) ($pr['draft'] ?? false),
                'reviewers' => count($pr['reviewers'] ?? []),
            ])->values()->all(),
            'issues' => $issues === null ? null : collect($issues['values'] ?? [])->map(fn (array $issue): array => [
                'number' => $issue['id'],
                'title' => $issue['title'],
                'url' => $issue['links']['html']['href'] ?? null,
                'author' => $issue['reporter']['display_name'] ?? null,
                'created_at' => $issue['created_on'] ?? null,
                'comments' => 0,
            ])->values()->all(),
        ];
    }

    protected function apiUrl(): string
    {
        return 'https://api.bitbucket.org/2.0';
    }

    /**
     * App passwords use the username; access tokens are sent as a bearer token.
     */
    protected function http(): PendingRequest
    {
        $request = parent::http();

        if (blank($this->account->token)) {
            return $request;
        }

        return filled($this->account->username)
            ? $request->withBasicAuth($this->account->username, $this->account->token)
            : $request->withToken($this->account->token);
    }
}
