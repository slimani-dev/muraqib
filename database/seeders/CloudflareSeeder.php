<?php

namespace Database\Seeders;

use App\Models\Cloudflare;
use App\Services\Cloudflare\CloudflareService;
use Illuminate\Database\Seeder;

class CloudflareSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accountId = config('services.cloudflare.seed_account_id');
        $apiToken = config('services.cloudflare.seed_api_token');

        if (! $accountId || ! $apiToken) {
            $this->command->warn('Skipping CloudflareSeeder: services.cloudflare.seed_account_id and services.cloudflare.seed_api_token not set in config');

            return;
        }

        $this->command->info('Creating Cloudflare account...');

        $cloudflare = Cloudflare::firstOrCreate(
            ['account_id' => $accountId],
            [
                'name' => 'Main Account',
                'api_token' => $apiToken,
                'status' => \App\Enums\CloudflareStatus::Active,
            ]
        );

        $this->command->info("✅ Cloudflare account created: {$cloudflare->name}");

        // Pull all data from Cloudflare
        $service = new CloudflareService;

        try {
            // 1. Pull Zones/Domains
            $this->command->info('Pulling zones from Cloudflare...');
            $zones = $service->listZones($apiToken);
            $zoneCount = 0;

            foreach ($zones as $zone) {
                $cloudflare->domains()->updateOrCreate(
                    ['zone_id' => $zone['id']],
                    [
                        'name' => $zone['name'],
                        'status' => $zone['status'],
                    ]
                );
                $zoneCount++;
            }
            $this->command->info("✅ Synced {$zoneCount} zones");

            // 2. Pull Tunnels
            $this->command->info('Pulling tunnels from Cloudflare...');
            $tunnels = $service->listTunnels($cloudflare);
            $tunnelCount = 0;

            foreach ($tunnels as $tunnel) {
                $cloudflare->tunnels()->updateOrCreate(
                    ['tunnel_id' => $tunnel['id']],
                    [
                        'name' => $tunnel['name'],
                        'status' => $tunnel['status'] ?? 'inactive',
                    ]
                );
                $tunnelCount++;
            }
            $this->command->info("✅ Synced {$tunnelCount} tunnels");

            // 3. Pull DNS Records for each domain
            $this->command->info('Pulling DNS records from Cloudflare...');
            $dnsCount = 0;

            foreach ($cloudflare->domains as $domain) {
                $records = $service->listDnsRecords($domain);

                foreach ($records as $record) {
                    $domain->dnsRecords()->updateOrCreate(
                        ['record_id' => $record['id']],
                        [
                            'type' => $record['type'],
                            'name' => $record['name'],
                            'content' => $record['content'],
                            'proxied' => $record['proxied'] ?? false,
                            'ttl' => $record['ttl'] ?? 0,
                        ]
                    );
                    $dnsCount++;
                }
            }
            $this->command->info("✅ Synced {$dnsCount} DNS records");

            // 4. Pull Service Tokens
            $this->command->info('Pulling service tokens from Cloudflare...');
            $tokens = $service->listServiceTokens($cloudflare);
            $tokenCount = 0;

            foreach ($tokens as $token) {
                // Check if already exists
                $existing = \App\Models\CloudflareAccess::where('service_token_id', $token['id'])->first();

                if ($existing) {
                    $existing->update(['name' => $token['name']]);

                    continue;
                }

                // Try to match to a domain
                $targetName = $token['name'];
                if (\Illuminate\Support\Str::startsWith($token['name'], 'Muraqib-')) {
                    $targetName = \Illuminate\Support\Str::after($token['name'], 'Muraqib-');
                }

                $matchedDomain = $cloudflare->domains
                    ->filter(fn ($d) => \Illuminate\Support\Str::endsWith($targetName, $d->name))
                    ->sortByDesc(fn ($d) => strlen($d->name))
                    ->first();

                $domainId = $matchedDomain?->id ?? $cloudflare->domains->first()?->id;

                if ($domainId) {
                    \App\Models\CloudflareAccess::create([
                        'cloudflare_domain_id' => $domainId,
                        'app_id' => null,
                        'name' => $targetName,
                        'client_id' => null,
                        'service_token_id' => $token['id'],
                        'client_secret' => null,
                        'policy_id' => null,
                    ]);
                    $tokenCount++;
                }
            }
            $this->command->info("✅ Synced {$tokenCount} service tokens");

            $this->command->info('🎉 Cloudflare seeding complete!');
        } catch (\Exception $e) {
            $this->command->error("Failed to sync Cloudflare data: {$e->getMessage()}");
        }
    }
}
