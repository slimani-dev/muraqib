<?php

namespace App\Traits;

use App\Models\CloudflareAccess;

trait WithCloudflareAccess
{
    /**
     * Resolve Cloudflare Access headers for a given host or list of hosts.
     *
     * @param  string|null  $host  The hostname (e.g., 'netdata.slimani.dev')
     */
    protected function getAccessHeaders(?string $host): array
    {
        if (! $host) {
            return [];
        }

        // Attempt to find a matching Access Token for the host
        // We assume the token name matches the hostname
        // Or we could match by 'name' ending with the host if token is generic?
        // Implementation Plan said: "Find CloudflareAccess record where name matches the host"

        $token = CloudflareAccess::where('name', $host)->first();

        if ($token) {
            return [
                'CF-Access-Client-Id' => $token->client_id,
                'CF-Access-Client-Secret' => $token->client_secret,
            ];
        }

        return [];
    }
}
