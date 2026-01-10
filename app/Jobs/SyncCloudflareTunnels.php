<?php

namespace App\Jobs;

use App\Enums\CloudflareStatus;
use App\Models\Cloudflare;
use App\Services\Cloudflare\CloudflareService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCloudflareTunnels implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(CloudflareService $service): void
    {
        Log::info('Checking Cloudflare status...');

        // chunking to handle many accounts if needed, though usually low volume
        Cloudflare::chunk(10, function ($accounts) use ($service) {
            foreach ($accounts as $account) {
                try {
                    // 1. Verify Account Token & Update Status
                    if (! $account->api_token) {
                        $account->update(['status' => CloudflareStatus::Inactive]);

                        continue;
                    }

                    $isValid = false;
                    try {
                        $isValid = $service->verifyToken($account->api_token);
                    } catch (\Exception $e) {
                        Log::error("Cloudflare ID {$account->id} check error: ".$e->getMessage());
                    }

                    $account->update([
                        'status' => $isValid ? CloudflareStatus::Active : CloudflareStatus::Inactive,
                    ]);

                    if (! $isValid) {
                        continue; // Skip tunnels if account is invalid
                    }

                    // 2. Sync Tunnels
                    $remoteTunnels = $service->listTunnels($account);

                    foreach ($remoteTunnels as $remoteTunnel) {
                        $localTunnel = $account->tunnels()->updateOrCreate(
                            ['tunnel_id' => $remoteTunnel['id']],
                            [
                                'name' => $remoteTunnel['name'],
                                // We don't overwrite status here yet, wait for detailed check
                            ]
                        );
                    }

                    // 3. Update Status for Local Tunnels
                    foreach ($account->tunnels as $tunnel) {
                        try {
                            $details = $service->getTunnelDetails($tunnel);
                            if ($details) {
                                $tunnel->update([
                                    'name' => $details['name'],
                                    'status' => $details['status'],
                                    'is_active' => ($details['status'] === 'healthy'),
                                    'conns_active_at' => $details['conns_active_at'] ?? null,
                                    'client_version' => $details['client_version'] ?? null,
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::error("Tunnel {$tunnel->id} sync error: ".$e->getMessage());
                        }
                    }

                } catch (\Exception $e) {
                    Log::error("Cloudflare sync failed for account {$account->id}: ".$e->getMessage());
                }
            }
        });
    }
}
