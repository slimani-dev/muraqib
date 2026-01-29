<?php

namespace Database\Seeders;

use App\Models\Portainer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class PortainerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $url = config('services.portainer.seed_url');
        $token = config('services.portainer.seed_token');

        if (!$url || !$token) {
            $this->command->warn('Skipping PortainerSeeder: services.portainer.seed_url and services.portainer.seed_token not set in config');
            return;
        }

        $this->command->info('Creating Portainer instance...');

        $portainer = Portainer::firstOrCreate(
            ['url' => $url],
            [
                'name' => 'Main Portainer',
                'access_token' => $token,
                'status' => \App\Enums\PortainerStatus::Active,
            ]
        );

        $this->command->info("✅ Portainer instance created: {$portainer->name}");

        // Pull all data from Portainer
        try {
            // 1. Pull Endpoints
            $this->command->info('Pulling endpoints from Portainer...');
            $response = Http::withHeader('X-API-Key', $token)->get("{$url}/api/endpoints");

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch endpoints: ' . $response->body());
            }

            $endpoints = $response->json();
            $endpointCount = 0;

            foreach ($endpoints as $endpoint) {
                $portainer->endpoints()->updateOrCreate(
                    ['endpoint_id' => $endpoint['Id']],
                    [
                        'name' => $endpoint['Name'],
                        'url' => $endpoint['URL'] ?? null,
                        'type' => $endpoint['Type'] ?? 1,
                        'status' => $endpoint['Status'] ?? 1,
                    ]
                );
                $endpointCount++;
            }
            $this->command->info("✅ Synced {$endpointCount} endpoints");

            // 2. Pull Stacks for each endpoint
            $this->command->info('Pulling stacks from Portainer...');
            $stackCount = 0;

            foreach ($portainer->endpoints as $endpoint) {
                $stacksResponse = Http::withHeader('X-API-Key', $token)
                    ->get("{$url}/api/stacks", [
                        'filters' => json_encode(['EndpointID' => $endpoint->endpoint_id])
                    ]);

                if ($stacksResponse->successful()) {
                    $stacks = $stacksResponse->json();

                    foreach ($stacks as $stack) {
                        $portainer->stacks()->updateOrCreate(
                            ['external_id' => $stack['Id']],
                            [
                                'portainer_endpoint_id' => $endpoint->id,
                                'name' => $stack['Name'],
                                'endpoint_id' => $endpoint->endpoint_id,
                                'stack_status' => $stack['Status'] ?? 1,
                                'stack_type' => $stack['Type'] ?? 1,
                            ]
                        );
                        $stackCount++;
                    }
                }
            }
            $this->command->info("✅ Synced {$stackCount} stacks");

            $this->command->info('🎉 Portainer seeding complete!');
        } catch (\Exception $e) {
            $this->command->error("Failed to sync Portainer data: {$e->getMessage()}");
        }
    }
}
