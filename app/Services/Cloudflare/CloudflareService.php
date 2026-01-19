<?php

namespace App\Services\Cloudflare;

use App\Models\Cloudflare;
use Illuminate\Support\Facades\Http;

class CloudflareService
{
    protected string $baseUrl = 'https://api.cloudflare.com/client/v4';

    /**
     * 1. Validate Token
     */
    public function verifyToken(string $token, ?string $accountId = null): bool
    {
        $endpoint = $accountId
            ? "$this->baseUrl/accounts/$accountId/tokens/verify"
            : "$this->baseUrl/user/tokens/verify";

        $response = Http::withToken($token)->get($endpoint);

        return $response->json('result.status') === 'active';
    }

    /**
     * 2. Create or Get Tunnel
     */
    public function findOrCreateTunnel(Cloudflare $account, string $name = 'muraqib-node')
    {
        // Check existing
        $list = Http::withToken($account->api_token)
            ->get("$this->baseUrl/accounts/{$account->account_id}/cfd_tunnel?is_deleted=false");

        $existing = collect($list->json('result'))->firstWhere('name', $name);

        if ($existing) {
            return $existing;
        }

        // Create new
        $response = Http::withToken($account->api_token)
            ->post("$this->baseUrl/accounts/{$account->account_id}/cfd_tunnel", [
                'name' => $name,
                'config_src' => 'cloudflare', // CRITICAL: Enables remote management
            ]);

        return $response->json('result');
    }

    /**
     * List Tunnels
     */
    public function listTunnels(Cloudflare $account)
    {
        $response = Http::withToken($account->api_token)
            ->get("$this->baseUrl/accounts/{$account->account_id}/cfd_tunnel?is_deleted=false");

        if (! $response->successful()) {
            return [];
        }

        return $response->json('result');
    }

    /**
     * Get Tunnel Details (Status, Connections)
     */
    public function getTunnelDetails(\App\Models\CloudflareTunnel $tunnel)
    {
        $tunnel->loadMissing('cloudflare');
        $account = $tunnel->cloudflare;

        $response = Http::withToken($account->api_token)
            ->get("$this->baseUrl/accounts/{$account->account_id}/cfd_tunnel/{$tunnel->tunnel_id}");

        if (! $response->successful()) {
            return null;
        }

        return $response->json('result');
    }

    /**
     * 3. Get Tunnel Token (Required for installation)
     */
    public function getTunnelToken(\App\Models\CloudflareTunnel $tunnel)
    {
        $tunnel->loadMissing('cloudflare');
        $account = $tunnel->cloudflare;

        $response = Http::withToken($account->api_token)
            ->get("$this->baseUrl/accounts/{$account->account_id}/cfd_tunnel/{$tunnel->tunnel_id}/token");

        if (! $response->successful()) {
            throw new \Exception('Failed to fetch tunnel token: '.$response->body());
        }

        $token = $response->json('result');

        if (! is_string($token) || strlen($token) < 50) {
            throw new \Exception('Invalid tunnel token received from Cloudflare.');
        }

        return $token;
    }

    /**
     * 4. Update Ingress Rules (Map Domains to Local Ports)
     */
    /**
     * 4. Update Ingress Rules (Map Domains to Local Ports)
     */
    public function updateIngressRules(\App\Models\CloudflareTunnel $tunnel)
    {
        $tunnel->loadMissing(['cloudflare', 'ingressRules']);
        $account = $tunnel->cloudflare;

        $ingress = [];
        $userCatchAll = null;

        // 1. Process specific rules first
        foreach ($tunnel->ingressRules as $rule) {
            if ($rule->is_catch_all) {
                // Keep the last defined catch-all
                $userCatchAll = $rule;

                continue;
            }

            // Skip rules without hostname that are NOT marked catch-all (invalid state, but safe to skip or treat as catch-all)
            // For now, let's assume if hostname is empty, it's a catch-all
            if (empty($rule->hostname)) {
                $userCatchAll = $rule;

                continue;
            }

            $item = [
                'service' => $rule->service,
                'hostname' => $rule->hostname,
            ];

            if ($rule->path) {
                $item['path'] = $rule->path;
            }

            if ($rule->origin_request) {
                $item['originRequest'] = $rule->origin_request;
            }

            $ingress[] = $item;
        }

        // 2. Append Catch-All Rule (User's or Default)
        if ($userCatchAll) {
            $item = [
                'service' => $userCatchAll->service,
            ];
            if ($userCatchAll->origin_request) {
                $item['originRequest'] = $userCatchAll->origin_request;
            }
            $ingress[] = $item;
        } else {
            // Force Default Catch-all 404
            $ingress[] = ['service' => 'http_status:404'];
        }

        $config = [
            'ingress' => $ingress,
        ];

        // Add specific global keys if they are supported in remote config
        // Note: 'warp-routing' is supported. 'originRequest' (global) is supported.
        // 'loglevel', 'protocol' are typically local-only CLI args, but let's check if we can pass them.
        // If not, we just ignore them for the remote config but keep them in DB for reference or local service generation.

        $response = Http::withToken($account->api_token)
            ->put("$this->baseUrl/accounts/{$account->account_id}/cfd_tunnel/{$tunnel->tunnel_id}/configurations", [
                'config' => $config,
            ]);

        if (! $response->successful()) {
            throw new \Exception('Cloudflare Error: '.$response->body());
        }

        return true;
    }

    /**
     * 5. Create DNS Record
     */
    public function createDnsRecord(\App\Models\CloudflareDomain $domain, \App\Models\CloudflareTunnel $tunnel, string $subdomain)
    {
        return $this->ensureCnameRecord($domain, $subdomain, "{$tunnel->tunnel_id}.cfargotunnel.com");
    }

    public function ensureCnameRecord(\App\Models\CloudflareDomain $domain, string $name, string $target)
    {
        $domain->loadMissing('cloudflare');
        $account = $domain->cloudflare;

        // 1. Check existing (ALL record types to prevent conflicts)
        $response = Http::withToken($account->api_token)
            ->get("$this->baseUrl/zones/{$domain->zone_id}/dns_records", [
                'name' => $name,
            ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to check DNS records: '.$response->body());
        }

        $records = $response->json('result');
        $existing = collect($records)->first();

        if ($existing) {
            // If it's not a CNAME, we can't safely proceed
            if ($existing['type'] !== 'CNAME') {
                throw new \Exception("A DNS record of type {$existing['type']} already exists for {$name}. Cannot create CNAME.");
            }

            if ($existing['content'] === $target) {
                return 'skipped';
            }

            // Update existing CNAME
            $update = Http::withToken($account->api_token)
                ->put("$this->baseUrl/zones/{$domain->zone_id}/dns_records/{$existing['id']}", [
                    'type' => 'CNAME',
                    'name' => $name,
                    'content' => $target,
                    'proxied' => true,
                    'ttl' => 1,
                ]);

            if (! $update->successful()) {
                throw new \Exception('Failed to update DNS record: '.$update->body());
            }

            return 'updated';
        }

        // 2. Create new
        $create = Http::withToken($account->api_token)
            ->post("$this->baseUrl/zones/{$domain->zone_id}/dns_records", [
                'type' => 'CNAME',
                'name' => $name,
                'content' => $target,
                'proxied' => true,
                'ttl' => 1,
            ]);

        if (! $create->successful()) {
            throw new \Exception('Failed to create DNS record: '.$create->body());
        }

        return 'created';
    }

    /**
     * List Zones
     */
    public function listZones(string $apiToken)
    {
        $response = Http::withToken($apiToken)->get("$this->baseUrl/zones");

        return $response->json('result');
    }

    /**
     * List DNS Records for a Zone
     */
    /**
     * List DNS Records for a Zone
     */
    public function listDnsRecords(\App\Models\CloudflareDomain $domain)
    {
        $domain->loadMissing('cloudflare');
        $account = $domain->cloudflare;

        $response = Http::withToken($account->api_token)
            ->get("$this->baseUrl/zones/{$domain->zone_id}/dns_records", [
                'per_page' => 100,
            ]);

        return $response->json('result');
    }

    public function createRemoteDnsRecord(\App\Models\CloudflareDomain $domain, array $data)
    {
        $domain->loadMissing('cloudflare');
        $account = $domain->cloudflare;

        $response = Http::withToken($account->api_token)
            ->post("$this->baseUrl/zones/{$domain->zone_id}/dns_records", $data);

        if (! $response->successful()) {
            throw new \Exception('Cloudflare Error: '.$response->body());
        }

        return $response->json('result');
    }

    public function updateRemoteDnsRecord(\App\Models\CloudflareDomain $domain, string $recordId, array $data)
    {
        $domain->loadMissing('cloudflare');
        $account = $domain->cloudflare;

        $response = Http::withToken($account->api_token)
            ->put("$this->baseUrl/zones/{$domain->zone_id}/dns_records/{$recordId}", $data);

        if (! $response->successful()) {
            throw new \Exception('Cloudflare Error: '.$response->body());
        }

        return $response->json('result');
    }

    public function deleteRemoteDnsRecord(\App\Models\CloudflareDomain $domain, string $recordId)
    {
        $domain->loadMissing('cloudflare');
        $account = $domain->cloudflare;

        $response = Http::withToken($account->api_token)
            ->delete("$this->baseUrl/zones/{$domain->zone_id}/dns_records/{$recordId}");

        return $response->successful();
    }

    /**
     * 6. Get Tunnel Configuration (Ingress Rules)
     */
    public function getTunnelConfig(\App\Models\CloudflareTunnel $tunnel)
    {
        $tunnel->loadMissing('cloudflare');
        $account = $tunnel->cloudflare;

        $response = Http::withToken($account->api_token)
            ->get("$this->baseUrl/accounts/{$account->account_id}/cfd_tunnel/{$tunnel->tunnel_id}/configurations");

        if (! $response->successful()) {
            return null;
        }

        return $response->json('result.config.ingress');
    }

    /**
     * 7. Protect Subdomain (Service Token + App + Policy)
     */
    public function protectSubdomain(\App\Models\CloudflareDomain $domain, string $subdomain)
    {
        $domain->loadMissing('cloudflare');
        $account = $domain->cloudflare;
        $apiToken = $account->api_token;
        $accountId = $account->account_id;

        // 1. Generate Service Token
        $tokenResponse = Http::withToken($apiToken)->post("$this->baseUrl/accounts/$accountId/access/service_tokens", [
            'name' => "Muraqib-$subdomain",
            'duration' => '8760h', // 1 Year
        ]);

        if (! $tokenResponse->successful()) {
            $error = $tokenResponse->json('errors.0.message') ?? $tokenResponse->body();
            throw new \Exception("Failed to generate Service Token: $error");
        }
        $tokenRes = $tokenResponse->json('result');

        // 2. Create Application
        $appResponse = Http::withToken($apiToken)->post("$this->baseUrl/accounts/$accountId/access/apps", [
            'type' => 'self_hosted',
            'name' => "Protect $subdomain",
            'domain' => $subdomain,
            'session_duration' => '24h',
        ]);

        if (! $appResponse->successful()) {
            $errorMsg = $appResponse->json('errors.0.message') ?? $appResponse->body();

            // Check for "application_already_exists" error (code 12136 or message text)
            // Or "uid_already_exists" if matching UID provided (not used here)
            // Cloudflare often returns: "access.api.error.application_already_exists"

            if (str_contains($errorMsg, 'application_already_exists')) {
                // Find existing app
                $existingApps = Http::withToken($apiToken)->get("$this->baseUrl/accounts/$accountId/access/apps")->json('result');
                $appRes = collect($existingApps)->firstWhere('domain', $subdomain); // Match by domain strictly

                if (! $appRes) {
                    // Fallback check by name
                    $appRes = collect($existingApps)->firstWhere('name', "Protect $subdomain");
                }

                if (! $appRes) {
                    throw new \Exception("Access Application exists but could not be found via API list. Error: $errorMsg");
                }
                // Reuse existing App
            } else {
                throw new \Exception("Failed to create Access Application: $errorMsg");
            }
        } else {
            $appRes = $appResponse->json('result');
        }

        // 3. Create Policy (Using reused or new App ID)
        $policyResponse = Http::withToken($apiToken)->post("$this->baseUrl/accounts/$accountId/access/apps/{$appRes['id']}/policies", [
            'name' => 'Allow Muraqib App',
            'decision' => 'non_identity',
            'include' => [['service_token' => ['token_id' => $tokenRes['id']]]],
        ]);

        if (! $policyResponse->successful()) {
            $error = $policyResponse->json('errors.0.message') ?? $policyResponse->body();

            // Check if policy already exists (cleanup from partial state)
            if (str_contains($error, 'policy_already_exists') || str_contains($error, 'Duplicate')) {
                // Try to find existing policy
                $policies = Http::withToken($apiToken)->get("$this->baseUrl/accounts/$accountId/access/apps/{$appRes['id']}/policies")->json('result');
                $existingPolicy = collect($policies)->firstWhere('name', 'Allow Muraqib App');

                if ($existingPolicy) {
                    // Update it to ensure it uses the NEW token
                    $updatePolicy = Http::withToken($apiToken)->put("$this->baseUrl/accounts/$accountId/access/apps/{$appRes['id']}/policies/{$existingPolicy['id']}", [
                        'name' => 'Allow Muraqib App',
                        'decision' => 'non_identity',
                        'include' => [['service_token' => ['token_id' => $tokenRes['id']]]],
                    ]);

                    if (! $updatePolicy->successful()) {
                        throw new \Exception('Failed to update existing Access Policy: '.$updatePolicy->body());
                    }
                    $policyRes = $updatePolicy->json('result');
                    // Success - fall through to return
                } else {
                    throw new \Exception("Policy 'Allow Muraqib App' reportedly exists but could not be found. Cloudflare Error: $error");
                }
            } else {
                throw new \Exception("Failed to create Access Policy: $error");
            }
        } else {
            $policyRes = $policyResponse->json('result');
        }

        return \App\Models\CloudflareAccess::create([
            'cloudflare_domain_id' => $domain->id,
            'app_id' => $appRes['id'],
            'name' => $subdomain,
            'client_id' => $tokenRes['client_id'],
            'client_secret' => $tokenRes['client_secret'],
            'policy_id' => $policyRes['id'],
        ]);
    }

    /**
     * List Service Tokens (Account Level)
     */
    public function listServiceTokens(\App\Models\Cloudflare $account)
    {
        $response = Http::withToken($account->api_token)
            ->get("$this->baseUrl/accounts/{$account->account_id}/access/service_tokens");

        if (! $response->successful()) {
            throw new \Exception('Failed to fetch service tokens: '.$response->body());
        }

        return $response->json('result');
    }

    /**
     * Delete Subdomain Protection (Service Token + App)
     */
    public function deleteSubdomainProtection(\App\Models\CloudflareDomain $domain, \App\Models\CloudflareAccess $access)
    {
        $domain->loadMissing('cloudflare');
        $account = $domain->cloudflare;
        // Check if account still exists. If soft deleted, we might still want to try if we have tokens.
        if (! $account) {
            return;
        }

        $apiToken = $account->api_token;
        $accountId = $account->account_id;

        // 1. Delete Service Token
        // We need the ID, which is stored in client_id field in DB for now?
        // Wait, database schema says: `client_id` (the header value) and `client_secret`.
        // The API DELETE endpoint needs the Service Token UUID (ID), not the Client ID (Service Token ID).
        // Let's check `createServiceToken` output. $tokenRes['id'] is the UUID.
        // We stored $tokenRes['client_id'] in `client_id` column.
        // Did we store the UUID?
        // Checking Database Migration...
        // Migration has: `client_id`, `client_secret`. It does NOT have a separate `token_id` column.
        // Does `client_id` == UUID?
        // Service Token API:
        // Response: { "id": "uuid", "client_id": "access-client-id...", ... }
        // So NO. We stored the Access Client ID, but we need the UUID to delete it.
        // Problem: We cannot delete the token efficiently without the UUID.
        // Solution: List tokens -> filter by client_id -> get UUID -> delete.

        if ($access->client_id) {
            $tokens = $this->listServiceTokens($account);
            $targetToken = collect($tokens)->firstWhere('client_id', $access->client_id); // Match by Client ID

            if ($targetToken) {
                Http::withToken($apiToken)->delete("$this->baseUrl/accounts/$accountId/access/service_tokens/{$targetToken['id']}");
            }
        }

        // 2. Delete Access Application
        if ($access->app_id) {
            Http::withToken($apiToken)->delete("$this->baseUrl/accounts/$accountId/access/apps/{$access->app_id}");
        }
    }
}
