<?php

namespace App\Actions\Cloudflare;

use App\Models\CloudflareTransformRule;
use App\Models\Netdata;
use App\Models\Portainer;
use App\Services\Cloudflare\CloudflareService;

class SyncTransformRules
{
    public function __construct(protected CloudflareService $service) {}

    public function handle(CloudflareTransformRule $rule)
    {
        // Load the Cloudflare account and linked services
        $rule->loadMissing(['cloudflare', 'netdatas.access', 'portainers']);
        $account = $rule->cloudflare;

        // Auto-generate pattern from linked services
        $hostnames = [];

        foreach ($rule->netdatas as $netdata) {
            if ($netdata->access && $netdata->access->name) {
                $hostnames[] = $netdata->access->name;
            }
        }

        foreach ($rule->portainers as $portainer) {
            $url = parse_url($portainer->url);
            if (isset($url['host'])) {
                $hostnames[] = $url['host'];
            }
        }

        if (empty($hostnames)) {
            throw new \Exception('No hostnames found from linked services. Please link at least one Netdata or Portainer instance.');
        }

        // Build regex pattern to match any of the linked hostnames
        $escapedHostnames = array_map(fn ($h) => preg_quote($h, '/'), $hostnames);
        $pattern = 'http.host matches "^('.implode('|', $escapedHostnames).')$"';

        // Auto-generate headers based on service type
        $headers = [];

        foreach ($rule->netdatas as $netdata) {
            if ($netdata->access) {
                // For Netdata, inject CF-Access credentials
                $headers['CF-Access-Client-Id'] = $netdata->access->client_id;
                $headers['CF-Access-Client-Secret'] = $netdata->access->client_secret;
                break; // Use first Netdata's credentials
            }
        }

        foreach ($rule->portainers as $portainer) {
            if ($portainer->access_token) {
                // For Portainer, inject Bearer token
                $headers['Authorization'] = 'Bearer '.$portainer->access_token;
                break; // Use first Portainer's token
            }
        }

        if (empty($headers)) {
            throw new \Exception('No authentication credentials found for linked services.');
        }

        // Save generated pattern and headers to the model
        $rule->pattern = $pattern;
        $rule->headers = $headers;
        $rule->save();

        // Get Zone ID from first domain
        $domain = $account->domains()->first();

        if (! $domain) {
            throw new \Exception('No domain found for Cloudflare account. Please add a domain first.');
        }

        $zoneId = $domain->zone_id;

        // Deploy to Cloudflare
        $ruleId = $this->service->createOrUpdateTransformRule(
            $account,
            $zoneId,
            $rule->name,
            $pattern,
            $headers,
            $rule->rule_ids[0] ?? null // Update existing if we have it
        );

        // Update Model
        $rule->rule_ids = [$ruleId];
        $rule->save();
    }
}
