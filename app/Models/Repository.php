<?php

namespace App\Models;

use App\Jobs\SyncRepository;
use Database\Factories\RepositoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A watched repository. Its provider data is synced by {@see SyncRepository}.
 *
 * @property string $owner
 * @property string $name
 * @property ?string $display_name
 * @property ?string $logo_url
 * @property bool $is_managed
 * @property ?array{tag: string, url: ?string, published_at: ?string} $latest_release
 * @property ?array{status: string, url: ?string, finished_at: ?string} $ci
 * @property ?list<array<string, mixed>> $pull_requests
 * @property ?list<array<string, mixed>> $issues
 * @property ?array{packagist?: array{total: int, monthly: int}, npm?: array{monthly: int}} $downloads
 */
class Repository extends Model
{
    /** @use HasFactory<RepositoryFactory> */
    use HasFactory;

    protected $fillable = [
        'git_account_id',
        'owner',
        'name',
        'display_name',
        'logo_url',
        'is_managed',
        'packagist_package',
        'npm_package',
        'description',
        'html_url',
        'default_branch',
        'language',
        'stars',
        'forks',
        'open_issues',
        'open_pull_requests',
        'pushed_at',
        'latest_release',
        'ci',
        'pull_requests',
        'issues',
        'downloads',
        'synced_at',
        'sync_error',
    ];

    protected function casts(): array
    {
        return [
            'is_managed' => 'boolean',
            'pushed_at' => 'datetime',
            'latest_release' => 'array',
            'ci' => 'array',
            'pull_requests' => 'array',
            'issues' => 'array',
            'downloads' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Sync right away when a repo is added or where it's read from changes.
     */
    protected static function booted(): void
    {
        static::saved(function (Repository $repository): void {
            if ($repository->wasRecentlyCreated || $repository->wasChanged(['git_account_id', 'owner', 'name', 'packagist_package', 'npm_package'])) {
                SyncRepository::dispatch($repository)->afterCommit();
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(GitAccount::class, 'git_account_id');
    }

    public function stats(): HasMany
    {
        return $this->hasMany(RepositoryStat::class);
    }

    public function fullName(): string
    {
        return "{$this->owner}/{$this->name}";
    }

    public function title(): string
    {
        return $this->display_name ?: $this->name;
    }

    /**
     * Total downloads across package registries: Packagist all-time when known, otherwise npm last month.
     */
    public function totalDownloads(): ?int
    {
        return $this->downloads['packagist']['total'] ?? $this->downloads['npm']['monthly'] ?? null;
    }

    /**
     * Downloads over the last month across Packagist and npm.
     */
    public function monthlyDownloads(): ?int
    {
        if (! $this->downloads) {
            return null;
        }

        return ($this->downloads['packagist']['monthly'] ?? 0) + ($this->downloads['npm']['monthly'] ?? 0);
    }
}
