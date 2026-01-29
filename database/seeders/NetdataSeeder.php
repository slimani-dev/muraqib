<?php

namespace Database\Seeders;

use App\Models\CloudflareIngressRule;
use App\Models\CloudflareTunnel;
use App\Models\Netdata;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NetdataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $urls = config('services.netdata.seed_urls', []);

        if (empty($urls)) {
            $this->command->warn('Skipping NetdataSeeder: services.netdata.seed_urls not set or empty in config');

            return;
        }

        $this->command->info('Seeding Netdata instances...');

        // We need a tunnel to attach ingress rules to.
        // In a real scenario, we might want to be more specific, but for seeding,
        // attaching to the first available tunnel is a reasonable default.
        $tunnel = CloudflareTunnel::first();

        if (! $tunnel) {
            $this->command->warn('No Cloudflare Tunnel found. Creating Netdata instances without Ingress Rules linked to a real tunnel.');
        }

        foreach ($urls as $url) {
            $parsed = parse_url($url);
            $host = $parsed['host'] ?? null;
            $scheme = $parsed['scheme'] ?? 'https';

            if (! $host) {
                $this->command->error("Invalid Netdata URL: {$url}");

                continue;
            }

            $name = Str::before($host, '.'); // e.g. 'netdata-proxmox' from 'netdata-proxmox.slimani.dev'

            $this->command->info("Processing {$name} ({$url})...");

            // Create or update Ingress Rule
            $ingressRule = null;
            if ($tunnel) {
                $ingressRule = CloudflareIngressRule::firstOrCreate(
                    [
                        'cloudflare_tunnel_id' => $tunnel->id,
                        'hostname' => $host,
                    ],
                    [
                        'service' => 'http://localhost:19999', // Default internal service
                        'path' => null,
                        'is_catch_all' => false,
                    ]
                );
            }

            // Create Netdata instance
            Netdata::updateOrCreate(
                ['name' => $name],
                [
                    'status' => 'active', // Assuming a simple status string or enum value
                    'cloudflare_ingress_rule_id' => $ingressRule?->id,
                    'disk_settings' => [],
                    'network_settings' => [],
                ]
            );
        }

        $this->command->info('✅ Netdata seeding complete!');
    }
}
