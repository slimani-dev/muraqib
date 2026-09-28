<?php

namespace App\Services\Git;

use App\Models\GitAccount;
use Closure;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Reads repositories from one Git host. Every provider returns the same shape
 * ({@see self::repository()}), so the sync job and widgets don't care where a repo lives.
 */
abstract class GitClient
{
    public function __construct(protected GitAccount $account) {}

    /**
     * Whether the host answers with the stored token. Backs the Filament "Test connection" action.
     */
    abstract public function checkConnection(): bool;

    /**
     * @return array{
     *     description: ?string, html_url: ?string, default_branch: ?string, language: ?string,
     *     stars: ?int, forks: ?int, open_issues: ?int, open_pull_requests: ?int, pushed_at: ?string,
     *     latest_release: ?array{tag: string, name: ?string, url: ?string, published_at: ?string},
     *     ci: ?array{status: string, url: ?string, finished_at: ?string},
     *     pull_requests: list<array{number: int|string, title: string, url: ?string, author: ?string, created_at: ?string, draft: bool, reviewers: int}>,
     *     issues: ?list<array{number: int|string, title: string, url: ?string, author: ?string, created_at: ?string, comments: int}>
     * }
     */
    abstract public function repository(string $owner, string $name): array;

    /**
     * The account's contribution calendar for the last year, when the host has one (GitHub only for now).
     *
     * @return array{username: string, total: int, commits: int, pull_requests: int, reviews: int, issues: int, private: int, weeks: list<list<array{date: string, count: int, level: int}>>}|null
     */
    public function contributions(): ?array
    {
        return null;
    }

    abstract protected function apiUrl(): string;

    /**
     * @return array<string, string>
     */
    protected function headers(): array
    {
        return [];
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl($this->apiUrl())
            ->acceptJson()
            ->withHeaders($this->headers())
            ->connectTimeout(5)
            ->timeout(15)
            ->throw();
    }

    /**
     * Run an optional request (latest release, CI, issues): a repo without releases or
     * with the issue tracker disabled answers 404, which just means "none".
     *
     * @template T
     *
     * @param  Closure(): T  $request
     * @return T|null
     */
    protected function optional(Closure $request): mixed
    {
        try {
            return $request();
        } catch (RequestException $e) {
            if (in_array($e->response->status(), [403, 404, 410], true)) {
                return null;
            }

            throw $e;
        }
    }

    protected function connects(string $path): bool
    {
        try {
            return $this->http()->get($path)->successful();
        } catch (HttpClientException) {
            return false;
        }
    }

    /**
     * Map a provider's CI state to success, failure, running, cancelled or unknown.
     */
    protected function ciStatus(?string $state): string
    {
        return match (strtolower((string) $state)) {
            'success', 'successful', 'passed' => 'success',
            'failure', 'failed', 'error', 'timed_out', 'startup_failure' => 'failure',
            'running', 'in_progress', 'queued', 'pending', 'waiting', 'created', 'requested', 'preparing' => 'running',
            'cancelled', 'canceled', 'stopped', 'skipped' => 'cancelled',
            default => 'unknown',
        };
    }
}
