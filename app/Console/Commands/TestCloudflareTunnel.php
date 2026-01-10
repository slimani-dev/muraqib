<?php

namespace App\Console\Commands;

use App\Models\Cloudflare;
use App\Services\Cloudflare\CloudflareService;
use Illuminate\Console\Command;

class TestCloudflareTunnel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:cloudflare-tunnel {account_id} {api_token}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually test Cloudflare Tunnel creation with provided credentials';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accountId = $this->argument('account_id');
        $apiToken = $this->argument('api_token');

        $this->info('Testing Cloudflare Tunnel Setup...');
        $this->info("Account ID: $accountId");
        $this->info('API Token: '.substr($apiToken, 0, 5).'...');

        $service = new CloudflareService;

        // 1. Verify Token
        $this->comment('1. Verifying Token...');
        try {
            $isValid = $service->verifyToken($apiToken);
            if ($isValid) {
                $this->info('✅ Token is valid.');
            } else {
                $this->error('❌ Token verification failed.');

                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception during verification: '.$e->getMessage());

            return 1;
        }

        // 2. Mock Config Object
        $config = new Cloudflare;
        $config->account_id = $accountId;
        $config->api_token = $apiToken; // Cast 'encrypted' handles this transparently

        // 3. Find or Create Tunnel
        $this->comment('2. Finding/Creating Tunnel (muraqib-node)...');
        try {
            $tunnel = $service->findOrCreateTunnel($config);
            $this->info('✅ Tunnel retrieved/created.');
            $this->table(['ID', 'Name', 'Status'], [
                [$tunnel['id'], $tunnel['name'], $tunnel['status'] ?? 'Unknown'],
            ]);

            $config->tunnel_id = $tunnel['id'];
            $config->tunnel_name = $tunnel['name'];

        } catch (\Exception $e) {
            $this->error('❌ Failed to find/create tunnel: '.$e->getMessage());

            return 1;
        }

        // 4. Get Tunnel Token
        $this->comment('3. Fetching Tunnel Token...');
        try {
            // Try standard endpoint
            $url1 = "https://api.cloudflare.com/client/v4/accounts/$accountId/tunnels/{$tunnel['id']}/token";
            $this->line("Attempting: $url1");
            $response1 = \Illuminate\Support\Facades\Http::withToken($apiToken)->get($url1);

            if ($response1->successful()) {
                $token = $response1->json('result');
                $this->info('✅ Valid Token Fetched (Standard Endpoint)!');
                $this->line('Token: '.substr($token, 0, 50).'...');

                return 0;
            } else {
                $this->warn('❌ Standard endpoint failed: '.$response1->status());
                $this->line('Body: '.$response1->body());
            }

            // Try cfd_tunnel endpoint (legacy/internal?)
            // Note: Docs says 'tunnels' is correct for Named Tunnels.
            // But let's verify if `cfd_tunnel` works.
            $url2 = "https://api.cloudflare.com/client/v4/accounts/$accountId/cfd_tunnel/{$tunnel['id']}/token";
            $this->line("Attempting: $url2");
            $response2 = \Illuminate\Support\Facades\Http::withToken($apiToken)->get($url2);

            if ($response2->successful()) {
                $token = $response2->json('result');
                $this->info('✅ Valid Token Fetched (CFD Endpoint)!');
                $this->line('Token: '.substr($token, 0, 50).'...');

                return 0;
            } else {
                $this->warn('❌ CFD endpoint failed: '.$response2->status());
                $this->line('Body: '.$response2->body());
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception: '.$e->getMessage());

            return 1;
        }

        $this->info('Test Complete.');
    }
}
