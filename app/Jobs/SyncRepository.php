<?php

namespace App\Jobs;

use App\Models\Repository;
use App\Services\Git\RepositorySyncer;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queued repository sync, on the low queue. Unique per repository, so a slow host
 * never piles up duplicate syncs. For an immediate sync call {@see RepositorySyncer} directly.
 */
class SyncRepository implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 600;

    public function __construct(public Repository $repository)
    {
        $this->onQueue('low');
    }

    public function uniqueId(): string
    {
        return (string) $this->repository->id;
    }

    public function handle(RepositorySyncer $syncer): void
    {
        $syncer->sync($this->repository);
    }
}
