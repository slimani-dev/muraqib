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
    public function verifyToken(string $token): bool
    {
        $response = Http::withToken($token)->get("$this->baseUrl/user/tokens/verify");

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
             throw new \Exception('Cloudflare Error: ' . $response->body());
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

        // 1. Check existing
        $response = Http::withToken($account->api_token)
            ->get("$this->baseUrl/zones/{$domain->zone_id}/dns_records", [
                'type' => 'CNAME',
                'name' => $name,
            ]);

        $existing = collect($response->json('result'))->first();

        if ($existing) {
             if ($existing['content'] === $target) {
                 return 'skipped';
             }

             // Update existing
             $update = Http::withToken($account->api_token)
                ->put("$this->baseUrl/zones/{$domain->zone_id}/dns_records/{$existing['id']}", [
                    'type' => 'CNAME',
                    'name' => $name,
                    'content' => $target,
                    'proxied' => true,
                    'ttl' => 1, // Auto
                ]);
             
             return $update->successful() ? 'updated' : 'error';
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

        return $create->successful() ? 'created' : 'error';
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
}
