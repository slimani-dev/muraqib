<?php

namespace App\Console\Commands;

use App\Models\Portainer;
use App\Services\PortainerService;
use Illuminate\Console\Command;

class SyncPortainerStacks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'portainer:sync-stacks {--force : Force sync ignoring cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync stacks from all Portainer instances';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $portainers = Portainer::all();
        $this->info("Found {$portainers->count()} Portainer instances.");

        foreach ($portainers as $portainer) {
            $this->info("Syncing stacks for: {$portainer->name}");

            try {
                $service = new PortainerService($portainer);
                $service->syncStacks($this->option('force'));
                $this->info('Done.');
            } catch (\Exception $e) {
                $this->error("Failed to sync {$portainer->name}: ".$e->getMessage());
            }
        }
    }
}
