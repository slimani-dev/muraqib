<?php

namespace App\Services\Cloudflare;

use App\Contracts\MonitorableService;
use App\Models\Cloudflare;
use App\Models\CloudflareAccess;
use App\Models\CloudflareDomain;
use App\Models\CloudflareTunnel;
use App\Traits\HasApiMonitoring;

class CloudflareService implements MonitorableService
{
    use HasApiMonitoring;

    public function getServiceName(): string
    {
        return 'Cloudflare';
    }

    protected string $baseUrl = 'https://api.cloudflare.com/client/v4';

    /**
     * 1. Validate Token
     */
    public function verifyToken(string $token, ?string $accountId = null): bool
    {
        $endpoint = $accountId
            ? "$this->baseUrl/accounts/$accountId/tokens/verify"
            : "$this->baseUrl/user/tokens/verify";

        $response = $this->http('verifyToken')->withToken($token)->get($endpoint);

        return $response->json('result.status') === 'active';
    }

    /**
     * 2. Create or Get Tunnel
     */
    public function findOrCreateTunnel(Cloudflare $account, string $name = 'muraqib-node')
    {
        // Check existing
        $list = $this->http('findOrCreateTunnel:list')->withToken($account->api_token)
            ->get("$this->baseUrl/accounts/{$account->account_id}/cfd_tunnel?is_deleted=false");

        $existing = collect($list->json('result'))->firstWhere('name', $name);

        if ($existing) {
            return $existing;
        }

        // Create new
        $response = $this->http('findOrCreateTunnel:create')->withToken($account->api_token)
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
        $response = $this->http('listTunnels')->withToken($account->api_token)
            ->get("$this->baseUrl/accounts/{$account->account_id}/cfd_tunnel?is_deleted=false");

        if (! $response->successful()) {
            return [];
        }

        return $response->json('result');
    }

    /**
     * Get Tunnel Details (Status, Connections)
     */
    public function getTunnelDetails(CloudflareTunnel $tunnel)
    {
        $tunnel->loadMissing('cloudflare');
        $account = $tunnel->cloudflare;

        $response = $this->http('getTunnelDetails')->withToken($account->api_token)
            ->get("$this->baseUrl/accounts/{$account->account_id}/cfd_tunnel/{$tunnel->tunnel_id}");

        if (! $response->successful()) {
            return null;
        }

        return $response->json('result');
    }

    /**
     * 3. Get Tunnel Token (Required for installation)
     */
    public function getTunnelToken(CloudflareTunnel $tunnel)
    {
        $tunnel->loadMissing('cloudflare');
        $account = $tunnel->cloudflare;

        $response = $this->http('getTunnelToken')->withToken($account->api_token)
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
    public function updateIngressRules(CloudflareTunnel $tunnel)
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

        $response = $this->http('updateIngressRules')->withToken($account->api_token)
            ->put("$this->baseUrl/accounts/{$account->account_id}/cfd_tunnel/{$tunnel->tunnel_id}/configurations", [
                'config' => $config,
            ]);

        if (! $response->successful()) {
            throw new \Exception('Cloudflare Error: '.$response->body());
        }

        return true;
    }

    /**
     * Purge specific URLs from Cloudflare's edge cache for a domain's zone.
     *
     * @param  array<int, string>  $urls
     */
    public function purgeUrls(CloudflareDomain $domain, array $urls): bool
    {
        $domain->loadMissing('cloudflare');
        $account = $domain->cloudflare;

        $response = $this->http('purgeUrls')->withToken($account->api_token)
            ->post("$this->baseUrl/zones/{$domain->zone_id}/purge_cache", [
                'files' => $urls,
            ]);

        if (! $response->successful()) {
            throw new \Exception('Cloudflare Error: '.$response->body());
        }

        return true;
    }

    /**
     * 5. Create DNS Record
     */
    public function createDnsRecord(CloudflareDomain $domain, CloudflareTunnel $tunnel, string $subdomain)
    {
        return $this->ensureCnameRecord($domain, $subdomain, "{$tunnel->tunnel_id}.cfargotunnel.com");
    }

    public function ensureCnameRecord(CloudflareDomain $domain, string $name, string $target)
    {
        $domain->loadMissing('cloudflare');
        $account = $domain->cloudflare;

        // 1. Check existing (ALL record types to prevent conflicts)
        $response = $this->http('ensureCnameRecord:check')->withToken($account->api_token)
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
            $update = $this->http('ensureCnameRecord:update')->withToken($account->api_token)
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
        $create = $this->http('ensureCnameRecord:create')->withToken($account->api_token)
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
        $response = $this->http('listZones')->withToken($apiToken)->get("$this->baseUrl/zones");

        return $response->json('result');
    }

    /**
     * List DNS Records for a Zone
     */
    /**
     * List DNS Records for a Zone
     */
    public function listDnsRecords(CloudflareDomain $domain)
    {
        $domain->loadMissing('cloudflare');
        $account = $domain->cloudflare;

        $response = $this->http('listDnsRecords')->withToken($account->api_token)
            ->get("$this->baseUrl/zones/{$domain->zone_id}/dns_records", [
                'per_page' => 100,
            ]);

        return $response->json('result') ?? [];
    }

    public function createRemoteDnsRecord(CloudflareDomain $domain, array $data)
    {
        $domain->loadMissing('cloudflare');
        $account = $domain->cloudflare;

        $response = $this->http('createRemoteDnsRecord')->withToken($account->api_token)
            ->post("$this->baseUrl/zones/{$domain->zone_id}/dns_records", $data);

        if (! $response->successful()) {
            throw new \Exception('Cloudflare Error: '.$response->body());
        }

        return $response->json('result');
    }

    public function updateRemoteDnsRecord(CloudflareDomain $domain, string $recordId, array $data)
    {
        $domain->loadMissing('cloudflare');
        $account = $domain->cloudflare;

        $response = $this->http('updateRemoteDnsRecord')->withToken($account->api_token)
            ->put("$this->baseUrl/zones/{$domain->zone_id}/dns_records/{$recordId}", $data);

        if (! $response->successful()) {
            throw new \Exception('Cloudflare Error: '.$response->body());
        }

        return $response->json('result');
    }

    public function deleteRemoteDnsRecord(CloudflareDomain $domain, string $recordId)
    {
        $domain->loadMissing('cloudflare');
        $account = $domain->cloudflare;

        $response = $this->http('deleteRemoteDnsRecord')->withToken($account->api_token)
            ->delete("$this->baseUrl/zones/{$domain->zone_id}/dns_records/{$recordId}");

        return $response->successful();
    }

    /**
     * 6. Get Tunnel Configuration (Ingress Rules)
     */
    public function getTunnelConfig(CloudflareTunnel $tunnel)
    {
        $tunnel->loadMissing('cloudflare');
        $account = $tunnel->cloudflare;

        $response = $this->http('getTunnelConfig')->withToken($account->api_token)
            ->get("$this->baseUrl/accounts/{$account->account_id}/cfd_tunnel/{$tunnel->tunnel_id}/configurations");

        if (! $response->successful()) {
            return null;
        }

        return $response->json('result.config.ingress');
    }

    /**
     * 7. Protect Subdomain (Service Token + App + Policy)
     */
    public function protectSubdomain(CloudflareDomain $domain, string $subdomain)
    {
        $domain->loadMissing('cloudflare');
        $account = $domain->cloudflare;
        $apiToken = $account->api_token;
        $accountId = $account->account_id;

        // 1. Generate Service Token
        $tokenResponse = $this->http('protectSubdomain:generateToken')->withToken($apiToken)->post("$this->baseUrl/accounts/$accountId/access/service_tokens", [
            'name' => "Muraqib-$subdomain",
            'duration' => '8760h', // 1 Year
        ]);

        if (! $tokenResponse->successful()) {
            $error = $tokenResponse->json('errors.0.message') ?? $tokenResponse->body();
            throw new \Exception("Failed to generate Service Token: $error");
        }
        $tokenRes = $tokenResponse->json('result');

        // 2. Create Application
        $appResponse = $this->http('protectSubdomain:createApp')->withToken($apiToken)->post("$this->baseUrl/accounts/$accountId/access/apps", [
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
                $existingApps = $this->http('protectSubdomain:findApp')->withToken($apiToken)->get("$this->baseUrl/accounts/$accountId/access/apps")->json('result');
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
        $policyResponse = $this->http('protectSubdomain:createPolicy')->withToken($apiToken)->post("$this->baseUrl/accounts/$accountId/access/apps/{$appRes['id']}/policies", [
            'name' => 'Allow Muraqib App',
            'decision' => 'non_identity',
            'include' => [['service_token' => ['token_id' => $tokenRes['id']]]],
        ]);

        if (! $policyResponse->successful()) {
            $error = $policyResponse->json('errors.0.message') ?? $policyResponse->body();

            // Check if policy already exists (cleanup from partial state)
            if (str_contains($error, 'policy_already_exists') || str_contains($error, 'Duplicate')) {
                // Try to find existing policy
                $policies = $this->http('protectSubdomain:findPolicy')->withToken($apiToken)->get("$this->baseUrl/accounts/$accountId/access/apps/{$appRes['id']}/policies")->json('result');
                $existingPolicy = collect($policies)->firstWhere('name', 'Allow Muraqib App');

                if ($existingPolicy) {
                    // Update it to ensure it uses the NEW token
                    $updatePolicy = $this->http('protectSubdomain:updatePolicy')->withToken($apiToken)->put("$this->baseUrl/accounts/$accountId/access/apps/{$appRes['id']}/policies/{$existingPolicy['id']}", [
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

        return CloudflareAccess::create([
            'cloudflare_domain_id' => $domain->id,
            'app_id' => $appRes['id'],
            'name' => $subdomain,
            'client_id' => $tokenRes['client_id'],  // Like: "30d8e9281b7b5698b0ae172d02c07803.access"
            'service_token_id' => $tokenRes['id'],  // Like: "09ff2772-def8-4086-8501-eb7ff62cf2fd" - THIS is what we need to delete!
            'client_secret' => $tokenRes['client_secret'],
            'policy_id' => $policyRes['id'],
        ]);
    }

    /**
     * List Service Tokens (Account Level)
     */
    public function listServiceTokens(Cloudflare $account)
    {
        $response = $this->http('listServiceTokens')->withToken($account->api_token)
            ->get("$this->baseUrl/accounts/{$account->account_id}/access/service_tokens");

        if (! $response->successful()) {
            throw new \Exception('Failed to fetch service tokens: '.$response->body());
        }

        return $response->json('result');
    }

    public function createServiceToken(Cloudflare $account, string $name, string $duration = '8760h')
    {
        $response = $this->http('createServiceToken')->withToken($account->api_token)
            ->post("$this->baseUrl/accounts/{$account->account_id}/access/service_tokens", [
                'name' => $name,
                'duration' => $duration,
            ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to create service token: '.$response->body());
        }

        return $response->json('result');
    }

    public function deleteServiceToken(Cloudflare $account, string $tokenId)
    {
        $response = $this->http('deleteServiceToken')->withToken($account->api_token)
            ->delete("$this->baseUrl/accounts/{$account->account_id}/access/service_tokens/$tokenId");

        if (! $response->successful()) {
            throw new \Exception('Failed to delete service token: '.$response->body());
        }

        return $response->successful();
    }

    /*
     * Access Applications
     */
    public function listAccessApplications(Cloudflare $account)
    {
        $response = $this->http('listAccessApplications')->withToken($account->api_token)
            ->get("$this->baseUrl/accounts/{$account->account_id}/access/apps");

        if (! $response->successful()) {
            throw new \Exception('Failed to list access applications: '.$response->body());
        }

        return $response->json('result');
    }

    public function createAccessApplication(Cloudflare $account, string $name, string $domain, string $type = 'self_hosted')
    {
        $response = $this->http('createAccessApplication')->withToken($account->api_token)
            ->post("$this->baseUrl/accounts/{$account->account_id}/access/apps", [
                'name' => $name,
                'domain' => $domain,
                'type' => $type,
                'session_duration' => '24h', // Default
            ]);

        if (! $response->successful()) {
            $error = $response->json('errors.0.message') ?? $response->body();
            throw new \Exception("Failed to create access application: $error");
        }

        return $response->json('result');
    }

    public function deleteAccessApplication(Cloudflare $account, string $appId)
    {
        $response = $this->http('deleteAccessApplication')->withToken($account->api_token)
            ->delete("$this->baseUrl/accounts/{$account->account_id}/access/apps/$appId");

        if (! $response->successful()) {
            throw new \Exception('Failed to delete access application: '.$response->body());
        }

        return $response->successful();
    }

    /*
     * Access Policies
     */
    public function listAccessPolicies(Cloudflare $account, string $appId)
    {
        $response = $this->http('listAccessPolicies')->withToken($account->api_token)
            ->get("$this->baseUrl/accounts/{$account->account_id}/access/apps/$appId/policies");

        if (! $response->successful()) {
            throw new \Exception('Failed to list access policies: '.$response->body());
        }

        return $response->json('result');
    }

    public function createAccessPolicy(Cloudflare $account, string $appId, string $name, string $decision, array $include)
    {
        // $include format: [['service_token' => ['token_id' => '...']]]

        $response = $this->http('createAccessPolicy')->withToken($account->api_token)
            ->post("$this->baseUrl/accounts/{$account->account_id}/access/apps/$appId/policies", [
                'name' => $name,
                'decision' => $decision,
                'include' => $include,
            ]);

        if (! $response->successful()) {
            $error = $response->json('errors.0.message') ?? $response->body();
            throw new \Exception("Failed to create access policy: $error");
        }

        return $response->json('result');
    }

    public function deleteAccessPolicy(Cloudflare $account, string $appId, string $policyId)
    {
        $response = $this->http('deleteAccessPolicy')->withToken($account->api_token)
            ->delete("$this->baseUrl/accounts/{$account->account_id}/access/apps/$appId/policies/$policyId");

        if (! $response->successful()) {
            throw new \Exception('Failed to delete access policy: '.$response->body());
        }

        return $response->successful();
    }

    /**
     * Delete Subdomain Protection (Service Token + App)
     *
     * @return array{success: bool, errors: array, deleted: array}
     */
    public function deleteSubdomainProtection(CloudflareDomain $domain, CloudflareAccess $access): array
    {
        $domain->loadMissing('cloudflare');
        $account = $domain->cloudflare;

        $results = [
            'success' => true,
            'errors' => [],
            'deleted' => [],
        ];

        // Check if account still exists. If soft deleted, we might still want to try if we have tokens.
        if (! $account) {
            $results['success'] = false;
            $results['errors'][] = 'Cloudflare account not found';

            return $results;
        }

        $apiToken = $account->api_token;
        $accountId = $account->account_id;

        // 1. Delete Policy (if exists) - Must be deleted BEFORE the app
        if ($access->policy_id && $access->app_id) {
            try {
                $response = $this->http('deleteSubdomainProtection:policy')->withToken($apiToken)->delete("$this->baseUrl/accounts/$accountId/access/apps/{$access->app_id}/policies/{$access->policy_id}");

                if ($response->successful()) {
                    $results['deleted'][] = "Policy {$access->policy_id}";
                } else {
                    $results['success'] = false;
                    $error = $response->json('errors.0.message') ?? $response->body();
                    $results['errors'][] = "Failed to delete policy: {$error}";
                }
            } catch (\Exception $e) {
                $results['success'] = false;
                $results['errors'][] = "Exception deleting policy: {$e->getMessage()}";
            }
        }

        // 2. Delete Access Application - Must be deleted BEFORE the service token
        if ($access->app_id) {
            try {
                $response = $this->http('deleteSubdomainProtection:app')->withToken($apiToken)->delete("$this->baseUrl/accounts/$accountId/access/apps/{$access->app_id}");

                if ($response->successful()) {
                    $results['deleted'][] = "Access App {$access->app_id}";
                } else {
                    $results['success'] = false;
                    $error = $response->json('errors.0.message') ?? $response->body();
                    $results['errors'][] = "Failed to delete access app: {$error}";
                }
            } catch (\Exception $e) {
                $results['success'] = false;
                $results['errors'][] = "Exception deleting access app: {$e->getMessage()}";
            }
        }

        // 3. Delete Service Token - Must be deleted LAST
        // Use service_token_id (the actual token ID), not client_id
        if ($access->service_token_id) {
            try {
                $response = $this->http('deleteSubdomainProtection:token')->withToken($apiToken)->delete("$this->baseUrl/accounts/$accountId/access/service_tokens/{$access->service_token_id}");

                if ($response->successful()) {
                    $results['deleted'][] = "Service Token {$access->service_token_id}";
                } else {
                    $results['success'] = false;
                    $error = $response->json('errors.0.message') ?? $response->body();
                    $results['errors'][] = "Failed to delete service token: {$error}";
                }
            } catch (\Exception $e) {
                $results['success'] = false;
                $results['errors'][] = "Exception deleting service token: {$e->getMessage()}";
            }
        }

        return $results;
    }

    /**
     * Scan for Service Token Usage
     */
    public function findTokenUsage(Cloudflare $account, ?string $tokenId): array
    {
        $usage = [
            'groups' => [],
            'policies' => [],
        ];

        if (! $tokenId) {
            return $usage;
        }

        // 1. Check Access Groups
        $groups = $this->http('findTokenUsage:groups')->withToken($account->api_token)
            ->get("$this->baseUrl/accounts/{$account->account_id}/access/groups", ['per_page' => 100])
            ->json('result') ?? [];

        foreach ($groups as $group) {
            if ($this->referencesToken($group, $tokenId)) {
                $usage['groups'][] = $group;
            }
        }

        // 2. Check Access Applications -> Policies
        $apps = $this->http('findTokenUsage:apps')->withToken($account->api_token)
            ->get("$this->baseUrl/accounts/{$account->account_id}/access/apps", ['per_page' => 100])
            ->json('result') ?? [];

        foreach ($apps as $app) {
            $policies = $this->http('findTokenUsage:policies')->withToken($account->api_token)
                ->get("$this->baseUrl/accounts/{$account->account_id}/access/apps/{$app['id']}/policies")
                ->json('result') ?? [];

            foreach ($policies as $policy) {
                if ($this->referencesToken($policy, $tokenId)) {
                    $policy['_app_name'] = $app['name']; // Attach app name for context
                    $policy['_app_id'] = $app['id'];
                    $usage['policies'][] = $policy;
                }
            }
        }

        return $usage;
    }

    protected function referencesToken(array $resource, string $tokenId): bool
    {
        $checks = ['include', 'exclude', 'require'];

        foreach ($checks as $check) {
            if (! isset($resource[$check]) || ! is_array($resource[$check])) {
                continue;
            }

            foreach ($resource[$check] as $rule) {
                if (isset($rule['service_token']['token_id']) && $rule['service_token']['token_id'] === $tokenId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Delete Service Token Dependencies (Recursive)
     */
    public function deleteTokenDependencies(Cloudflare $account, string $tokenId): array
    {
        $usage = $this->findTokenUsage($account, $tokenId);
        $deleted = [];
        $errors = [];

        // Delete/Update Policies
        foreach ($usage['policies'] as $policy) {
            // Delete the policy entirely
            $res = $this->http('deleteTokenDependencies:policy')->withToken($account->api_token)
                ->delete("$this->baseUrl/accounts/{$account->account_id}/access/apps/{$policy['_app_id']}/policies/{$policy['id']}");

            if ($res->successful()) {
                $deleted[] = "Policy: {$policy['name']} (in {$policy['_app_name']})";
            } else {
                $errors[] = "Failed to delete Policy {$policy['name']}: ".$res->body();
            }
        }

        // Delete Groups
        foreach ($usage['groups'] as $group) {
            $res = $this->http('deleteTokenDependencies:group')->withToken($account->api_token)
                ->delete("$this->baseUrl/accounts/{$account->account_id}/access/groups/{$group['id']}");

            if ($res->successful()) {
                $deleted[] = "Group: {$group['name']}";
            } else {
                $errors[] = "Failed to delete Group {$group['name']}: ".$res->body();
            }
        }

        return ['deleted' => $deleted, 'errors' => $errors];
    }
}
