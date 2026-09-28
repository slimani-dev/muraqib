<?php

namespace App\Models;

use App\Enums\GitProvider;
use App\Services\Git\ContributionsSyncer;
use App\Services\Git\GitClient;
use Database\Factories\GitAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A login on a Git host (GitHub, GitLab, Gitea, Forgejo, Codeberg, Bitbucket) used to read repositories.
 *
 * @property GitProvider $provider
 * @property string $name
 * @property ?string $base_url
 * @property ?string $username
 * @property ?string $token
 * @property ?array{username: string, total: int, commits: int, pull_requests: int, reviews: int, issues: int, private: int, weeks: list<list<array{date: string, count: int, level: int}>>} $contributions
 */
class GitAccount extends Model
{
    /** @use HasFactory<GitAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'provider',
        'name',
        'base_url',
        'username',
        'token',
        'contributions',
        'contributions_synced_at',
    ];

    protected $hidden = [
        'token',
    ];

    protected function casts(): array
    {
        return [
            'provider' => GitProvider::class,
            'token' => 'encrypted',
            'contributions' => 'array',
            'contributions_synced_at' => 'datetime',
        ];
    }

    /**
     * Fetch the contribution calendar right away for a new account or a new token.
     */
    protected static function booted(): void
    {
        static::saved(function (GitAccount $account): void {
            if ($account->wasRecentlyCreated || $account->wasChanged(['token', 'base_url', 'provider'])) {
                dispatch(fn () => app(ContributionsSyncer::class)->sync($account))->onQueue('low')->afterCommit();
            }
        });
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(Repository::class);
    }

    /**
     * The web address of the host, e.g. https://github.com or a self-hosted Gitea.
     */
    public function webUrl(): string
    {
        return rtrim($this->base_url ?: (string) $this->provider->defaultBaseUrl(), '/');
    }

    public function client(): GitClient
    {
        $clientClass = $this->provider->clientClass();

        return new $clientClass($this);
    }
}
