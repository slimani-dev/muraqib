<?php

namespace App\Services\Git;

use App\Models\Repository;
use Throwable;

/**
 * Pull a repository's stats, PRs, issues, release and CI state from its Git host (and downloads
 * from Packagist / npm) into the database, and record today's counters. Widgets only read the
 * database, so the dashboard never calls a Git host itself.
 */
class RepositorySyncer
{
    public function __construct(private PackageDownloads $packageDownloads) {}

    /**
     * @return bool Whether the sync worked. On failure the last data is kept and `sync_error` is set.
     */
    public function sync(Repository $repository): bool
    {
        try {
            $data = $repository->account->client()->repository($repository->owner, $repository->name);
        } catch (Throwable $e) {
            // Network errors, but also a response that isn't shaped as expected
            report($e);
            $repository->update(['sync_error' => $e->getMessage()]);

            return false;
        }

        $downloads = array_filter([
            'packagist' => $repository->packagist_package ? $this->packageDownloads->packagist($repository->packagist_package) : null,
            'npm' => $repository->npm_package ? $this->packageDownloads->npm($repository->npm_package) : null,
        ]);

        $repository->update([
            ...$data,
            'downloads' => $downloads ?: null,
            'synced_at' => now(),
            'sync_error' => null,
        ]);

        $repository->stats()->updateOrCreate(
            ['date' => today()->toDateString()],
            ['stars' => $repository->stars, 'forks' => $repository->forks, 'downloads' => $repository->totalDownloads()],
        );

        return true;
    }
}
