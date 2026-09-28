<?php

namespace App\Console\Commands;

use App\Jobs\SyncRepository;
use App\Models\GitAccount;
use App\Models\Repository;
use App\Services\Git\ContributionsSyncer;
use App\Services\Git\RepositorySyncer;
use Illuminate\Console\Command;

class SyncRepositories extends Command
{
    protected $signature = 'repositories:sync {--now : Sync in this process instead of queueing}';

    protected $description = 'Sync every watched repository (queued on the low queue) and each account\'s contribution calendar';

    public function handle(RepositorySyncer $syncer, ContributionsSyncer $contributions): int
    {
        // One small request per account, so it runs right here even when repositories are queued
        GitAccount::query()->each(fn (GitAccount $account) => $contributions->sync($account));

        $count = 0;

        Repository::query()->with('account')->each(function (Repository $repository) use (&$count, $syncer): void {
            $this->option('now') ? $syncer->sync($repository) : SyncRepository::dispatch($repository);
            $count++;
        });

        $this->components->info(($this->option('now') ? 'Synced' : 'Queued')." {$count} repositories.");

        return self::SUCCESS;
    }
}
