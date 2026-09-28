<?php

namespace App\Console\Commands;

use App\Enums\MediaServiceType;
use App\Models\MediaService;
use Illuminate\Console\Command;

/**
 * One-off upgrade helper: media services used to be configured with
 * JELLYFIN_URL, RADARR_API_KEY, ... in .env. This copies them into the
 * media_services table, which is managed in the admin panel.
 */
class ImportMediaServicesFromEnv extends Command
{
    protected $signature = 'media:import-env';

    protected $description = 'Import media services configured in .env (JELLYFIN_URL, RADARR_API_KEY, ...) into the database';

    public function handle(): int
    {
        $imported = 0;

        foreach (MediaServiceType::cases() as $type) {
            $prefix = strtoupper($type->value);
            $url = env("{$prefix}_URL");

            if (blank($url)) {
                continue;
            }

            $attributes = $type->usesBasicAuth()
                ? [
                    'username' => env("{$prefix}_USERNAME"),
                    'password' => env("{$prefix}_PASSWORD"),
                    'settings' => ['rpc_path' => env("{$prefix}_RPC_PATH", '/transmission/rpc')],
                ]
                : ['api_key' => env("{$prefix}_API_KEY")];

            $service = MediaService::query()->firstOrNew(['type' => $type]);
            $service->fill([
                'name' => $service->name ?? $type->getLabel(),
                'url' => rtrim($url, '/'),
                ...$attributes,
            ])->save();

            $this->components->info("{$type->getLabel()}: ".($service->wasRecentlyCreated ? 'imported' : 'updated')." ({$service->url})");
            $imported++;
        }

        if ($imported === 0) {
            $this->components->warn('No media services found in .env.');
        }

        return self::SUCCESS;
    }
}
