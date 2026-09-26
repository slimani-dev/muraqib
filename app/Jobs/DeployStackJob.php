<?php

namespace App\Jobs;

use App\Models\Portainer;
use App\Models\Stack;
use App\Models\User;
use App\Services\PortainerService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DeployStackJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Portainer $portainer,
        public Stack $stack,
        public string $stackFileContent,
        public array $env,
        public bool $prune = false,
        public bool $pullImage = false,
        public ?User $user = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $service = new PortainerService($this->portainer);

        $success = $service->updateStack(
            (int) $this->stack->external_id,
            $this->stack->endpoint_id,
            $this->stackFileContent,
            $this->env,
            $this->prune,
            $this->pullImage,
        );

        if ($success) {
            Log::info("DeployStackJob: Stack '{$this->stack->name}' deployed successfully.");

            if ($this->user) {
                Notification::make()
                    ->title('Deployment Successful')
                    ->body("Stack '{$this->stack->name}' has been deployed successfully.")
                    ->success()
                    ->sendToDatabase($this->user);
            }

            // Wait briefly for Portainer to apply changes, then sync
            sleep(5);
            $service->syncStacks(force: true);
            $service->syncContainers(force: true);
        } else {
            Log::error("DeployStackJob: Failed to deploy stack '{$this->stack->name}'.");

            if ($this->user) {
                Notification::make()
                    ->title('Deployment Failed')
                    ->body("Failed to deploy stack '{$this->stack->name}'. Check logs for details.")
                    ->danger()
                    ->sendToDatabase($this->user);
            }
        }
    }
}
