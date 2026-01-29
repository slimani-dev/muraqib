<?php

namespace App\Console\Commands;

use App\Models\Cloudflare;
use App\Services\Cloudflare\CloudflareService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CloudflareAccessOverview extends Command
{
    protected $signature = 'cloudflare:overview';

    protected $description = 'Display all Cloudflare resources accessible via the registered API token';

    protected CloudflareService $cloudflare;

    protected const BASE_URL = 'https://api.cloudflare.com/client/v4';

    public function __construct(CloudflareService $cloudflare)
    {
        parent::__construct();
        $this->cloudflare = $cloudflare;
    }

    public function handle(): int
    {
        $this->info('🔍 Fetching Cloudflare Account Information...');
        $this->newLine();

        $account = Cloudflare::first();

        if (! $account) {
            $this->error('❌ No Cloudflare account found in database.');

            return self::FAILURE;
        }

        $this->displayAccountInfo($account);
        $this->displayZones($account);
        $this->displayTunnels($account);
        $this->displayAccessResources($account);

        $this->newLine();
        $this->info('✅ Overview completed successfully!');

        return self::SUCCESS;
    }

    protected function displayAccountInfo(Cloudflare $account): void
    {
        $this->components->twoColumnDetail('Account Name', $account->name);
        $this->components->twoColumnDetail('Account ID', $account->account_id);
        $this->components->twoColumnDetail('Status', $account->status->value);
        $this->newLine();
    }

    protected function displayZones(Cloudflare $account): void
    {
        $this->info('🌐 Zones');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        try {
            $zones = $this->cloudflare->listZones($account->api_token);

            if (empty($zones)) {
                $this->comment('  No zones found.');
                $this->newLine();

                return;
            }

            foreach ($zones as $zone) {
                $this->newLine();
                $this->line("  <fg=cyan>Zone:</> {$zone['name']}");
                $this->components->twoColumnDetail('  Zone ID', $zone['id']);
                $this->components->twoColumnDetail('  Status', $zone['status']);
                $this->components->twoColumnDetail('  Name Servers', implode(', ', $zone['name_servers'] ?? []));

                // Display DNS Records for this zone
                $this->displayDnsRecords($account, $zone['id']);

                // Display Transform Rules for this zone
                $this->displayTransformRules($account, $zone['id']);
            }

            $this->newLine();
        } catch (\Exception $e) {
            $this->error("  Failed to fetch zones: {$e->getMessage()}");
            $this->newLine();
        }
    }

    protected function displayDnsRecords(Cloudflare $account, string $zoneId): void
    {
        try {
            $response = Http::withToken($account->api_token)
                ->get(self::BASE_URL."/zones/{$zoneId}/dns_records", [
                    'per_page' => 100,
                ]);

            if (! $response->successful()) {
                return;
            }

            $records = $response->json('result');

            if (empty($records)) {
                return;
            }

            $this->newLine();
            $this->line('  <fg=yellow>DNS Records:</>');

            $tableData = collect($records)->map(function ($record) {
                return [
                    'type' => $record['type'],
                    'name' => $record['name'],
                    'content' => $record['content'],
                    'proxied' => $record['proxied'] ? '🟢 Yes' : '⚪ No',
                    'ttl' => $record['ttl'] === 1 ? 'Auto' : $record['ttl'],
                ];
            })->toArray();

            $this->table(
                ['Type', 'Name', 'Content', 'Proxied', 'TTL'],
                $tableData
            );
        } catch (\Exception $e) {
            $this->warn("    Failed to fetch DNS records: {$e->getMessage()}");
        }
    }

    protected function displayTransformRules(Cloudflare $account, string $zoneId): void
    {
        try {
            $response = Http::withToken($account->api_token)
                ->get(self::BASE_URL."/zones/{$zoneId}/rulesets", [
                    'phase' => 'http_request_late_transform',
                ]);

            if (! $response->successful()) {
                return;
            }

            $rulesets = $response->json('result');
            $transformRuleset = collect($rulesets)->firstWhere('phase', 'http_request_late_transform');

            if (! $transformRuleset || empty($transformRuleset['rules'])) {
                return;
            }

            $this->newLine();
            $this->line('  <fg=magenta>Transform Rules:</>');

            foreach ($transformRuleset['rules'] as $rule) {
                $this->line("    • {$rule['description']}");
                $this->components->twoColumnDetail('      Expression', $rule['expression']);
                $this->components->twoColumnDetail('      Enabled', $rule['enabled'] ? '✅' : '❌');

                if (! empty($rule['action_parameters']['headers'])) {
                    $this->line('      <fg=yellow>Headers:</>');
                    foreach ($rule['action_parameters']['headers'] as $header => $config) {
                        $value = is_array($config) ? ($config['value'] ?? 'N/A') : $config;
                        $this->line("        - {$header}: {$value}");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->warn("    Failed to fetch transform rules: {$e->getMessage()}");
        }
    }

    protected function displayTunnels(Cloudflare $account): void
    {
        $this->info('🚇 Tunnels');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        try {
            $tunnels = $this->cloudflare->listTunnels($account);

            if (empty($tunnels)) {
                $this->comment('  No tunnels found.');
                $this->newLine();

                return;
            }

            foreach ($tunnels as $tunnel) {
                $this->newLine();
                $this->line("  <fg=cyan>Tunnel:</> {$tunnel['name']}");
                $this->components->twoColumnDetail('  Tunnel ID', $tunnel['id']);
                $this->components->twoColumnDetail('  Status', $tunnel['status'] ?? 'inactive');
                $this->components->twoColumnDetail('  Created', $tunnel['created_at'] ?? 'N/A');

                // Get tunnel configuration
                $this->displayTunnelConfig($account, $tunnel['id']);

                // Get tunnel connections
                $this->displayTunnelConnections($tunnel);
            }

            $this->newLine();
        } catch (\Exception $e) {
            $this->error("  Failed to fetch tunnels: {$e->getMessage()}");
            $this->newLine();
        }
    }

    protected function displayTunnelConfig(Cloudflare $account, string $tunnelId): void
    {
        try {
            $response = Http::withToken($account->api_token)
                ->get(self::BASE_URL."/accounts/{$account->account_id}/cfd_tunnel/{$tunnelId}/configurations");

            if (! $response->successful()) {
                return;
            }

            $config = $response->json('result.config.ingress');

            if (empty($config)) {
                return;
            }

            $this->newLine();
            $this->line('  <fg=yellow>Ingress Rules:</>');

            foreach ($config as $rule) {
                if (isset($rule['hostname'])) {
                    $this->line("    • {$rule['hostname']} → {$rule['service']}");
                    if (isset($rule['path'])) {
                        $this->components->twoColumnDetail('      Path', $rule['path']);
                    }
                } else {
                    $this->line("    • Catch-all → {$rule['service']}");
                }
            }
        } catch (\Exception $e) {
            $this->warn("    Failed to fetch tunnel config: {$e->getMessage()}");
        }
    }

    protected function displayTunnelConnections(array $tunnel): void
    {
        if (empty($tunnel['connections'])) {
            return;
        }

        $this->newLine();
        $this->line('  <fg=green>Active Connections:</>');

        foreach ($tunnel['connections'] as $connection) {
            $this->line("    • {$connection['colo_name']} ({$connection['id']})");
            $this->components->twoColumnDetail('      Client ID', $connection['client_id'] ?? 'N/A');
            $this->components->twoColumnDetail('      Opened', $connection['opened_at'] ?? 'N/A');
        }
    }

    protected function displayAccessResources(Cloudflare $account): void
    {
        $this->info('🔐 Access Resources');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Display Service Tokens
        $this->displayServiceTokens($account);

        // Display Access Applications
        $this->displayAccessApplications($account);

        $this->newLine();
    }

    protected function displayServiceTokens(Cloudflare $account): void
    {
        try {
            $tokens = $this->cloudflare->listServiceTokens($account);

            $this->newLine();
            $this->line('  <fg=yellow>Service Tokens:</>');

            if (empty($tokens)) {
                $this->comment('    No service tokens found.');

                return;
            }

            $tableData = collect($tokens)->map(function ($token) {
                return [
                    'name' => $token['name'],
                    'id' => $token['id'],
                    'expires' => $token['expires_at'] ?? 'Never',
                    'created' => $token['created_at'] ?? 'N/A',
                ];
            })->toArray();

            $this->table(
                ['Name', 'ID', 'Expires', 'Created'],
                $tableData
            );
        } catch (\Exception $e) {
            $this->warn("    Failed to fetch service tokens: {$e->getMessage()}");
        }
    }

    protected function displayAccessApplications(Cloudflare $account): void
    {
        try {
            $response = Http::withToken($account->api_token)
                ->get(self::BASE_URL."/accounts/{$account->account_id}/access/apps");

            if (! $response->successful()) {
                return;
            }

            $apps = $response->json('result');

            $this->newLine();
            $this->line('  <fg=cyan>Access Applications:</>');

            if (empty($apps)) {
                $this->comment('    No access applications found.');

                return;
            }

            foreach ($apps as $app) {
                $this->newLine();
                $this->line("    • {$app['name']}");
                $this->components->twoColumnDetail('      Domain', $app['domain'] ?? 'N/A');
                $this->components->twoColumnDetail('      Type', $app['type'] ?? 'N/A');
                $this->components->twoColumnDetail('      Session Duration', $app['session_duration'] ?? 'N/A');

                // Display policies for this app
                $this->displayAccessPolicies($account, $app['id']);
            }
        } catch (\Exception $e) {
            $this->warn("    Failed to fetch access applications: {$e->getMessage()}");
        }
    }

    protected function displayAccessPolicies(Cloudflare $account, string $appId): void
    {
        try {
            $response = Http::withToken($account->api_token)
                ->get(self::BASE_URL."/accounts/{$account->account_id}/access/apps/{$appId}/policies");

            if (! $response->successful()) {
                return;
            }

            $policies = $response->json('result');

            if (empty($policies)) {
                return;
            }

            $this->line('      <fg=magenta>Policies:</>');

            foreach ($policies as $policy) {
                $this->line("        - {$policy['name']} ({$policy['decision']})");
            }
        } catch (\Exception $e) {
            // Silently fail for policies
        }
    }
}
