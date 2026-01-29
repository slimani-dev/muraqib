<?php

namespace App\Console\Commands;

use App\Models\Cloudflare;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FindServiceTokenUsage extends Command
{
    protected $signature = 'cloudflare:find-token-usage {token : The Service Token ID or Name}';

    protected $description = 'Find where a Cloudflare Service Token is being used (Policies, Groups, etc.)';

    protected const BASE_URL = 'https://api.cloudflare.com/client/v4';

    public function handle(): int
    {
        $input = $this->argument('token');
        
        $account = Cloudflare::first();
        if (! $account) {
            $this->error('❌ No Cloudflare account configured.');
            return self::FAILURE;
        }

        $this->info("🔍 Searching for usage of token: {$input}");

        // 1. Resolve Token ID
        $token = $this->resolveToken($account, $input);
        if (! $token) {
            $this->error("❌ Service Token not found: {$input}");
            return self::FAILURE;
        }

        $tokenId = $token['id'];
        $tokenName = $token['name'];

        $this->info("✅ Found Token: {$tokenName} ({$tokenId})");
        $this->newLine();

        $foundUsage = false;

        // 2. Check Access Groups
        $this->info('🔎 Scanning Access Groups...');
        $groups = $this->fetchAccessGroups($account);
        foreach ($groups as $group) {
            if ($this->referencesToken($group, $tokenId)) {
                $this->error("  ⚠️  Found in Group: {$group['name']} ({$group['id']})");
                $foundUsage = true;
            }
        }

        // 3. Check Access Applications & Policies
        $this->info('🔎 Scanning Access Applications & Policies...');
        $apps = $this->fetchAccessApps($account);

        foreach ($apps as $app) {
            $policies = $this->fetchAppPolicies($account, $app['id']);
            foreach ($policies as $policy) {
                if ($this->referencesToken($policy, $tokenId)) {
                    $this->error("  ⚠️  Found in App: [{$app['name']}] -> Policy: [{$policy['name']}]");
                     $this->line("      Policy ID: {$policy['id']}");
                     $this->line("      App ID: {$app['id']}");
                    $foundUsage = true;
                }
            }
        }

        if (! $foundUsage) {
            $this->info('✨ No usage found for this Service Token in Groups or Policies.');
            $this->comment('Note: It might still be used in SCIM integrations or other granular settings not scanned.');
        } else {
            $this->newLine();
            $this->warn('❗ You must remove the Service Token from the above resources before deleting it.');
        }

        return self::SUCCESS;
    }

    protected function resolveToken(Cloudflare $account, string $input): ?array
    {
        $response = Http::withToken($account->api_token)
            ->get(self::BASE_URL."/accounts/{$account->account_id}/access/service_tokens");

        if (! $response->successful()) {
            $this->error('Failed to list service tokens: ' . $response->body());
            return null;
        }

        $tokens = $response->json('result');

        // Try Exact Match on ID
        $byId = collect($tokens)->firstWhere('id', $input);
        if ($byId) return $byId;

        // Try Match on Name (or partial?)
        $byName = collect($tokens)->firstWhere('name', $input);
        if ($byName) return $byName;

        return null;
    }

    protected function fetchAccessGroups(Cloudflare $account): array
    {
        $response = Http::withToken($account->api_token)
            ->get(self::BASE_URL."/accounts/{$account->account_id}/access/groups");

        return $response->json('result') ?? [];
    }

    protected function fetchAccessApps(Cloudflare $account): array
    {
        $response = Http::withToken($account->api_token)
            ->get(self::BASE_URL."/accounts/{$account->account_id}/access/apps");

        return $response->json('result') ?? [];
    }

    protected function fetchAppPolicies(Cloudflare $account, string $appId): array
    {
        $response = Http::withToken($account->api_token)
            ->get(self::BASE_URL."/accounts/{$account->account_id}/access/apps/{$appId}/policies");

        return $response->json('result') ?? [];
    }

    protected function referencesToken(array $resource, string $tokenId): bool
    {
        // Check include, exclude, require arrays
        $checks = ['include', 'exclude', 'require'];

        foreach ($checks as $check) {
            if (! isset($resource[$check]) || ! is_array($resource[$check])) {
                continue;
            }

            foreach ($resource[$check] as $rule) {
                // Rule structure: ['service_token' => ['token_id' => '...']]
                // Or potentially flat if using different rule structure, but standard is nested.
                
                if (isset($rule['service_token']['token_id']) && $rule['service_token']['token_id'] === $tokenId) {
                    return true;
                }
            }
        }

        return false;
    }
}
