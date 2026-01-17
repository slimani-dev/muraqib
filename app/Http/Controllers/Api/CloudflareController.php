<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cloudflare;
use App\Services\Cloudflare\CloudflareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CloudflareController extends Controller
{
    public function __construct(protected CloudflareService $cloudflare) {}

    public function status(Request $request)
    {
        $account = Cloudflare::first();

        if (! $account) {
            return response()->json(['status' => 'inactive']);
        }

        $tunnels = $account->tunnels; // Relation hasMany

        if ($tunnels->isEmpty()) {
            return response()->json(['status' => 'inactive']);
        }

        // Return status of the first tunnel for backward compatibility or simple summary
        // Ideally we return all tunnels.
        $tunnel = $tunnels->first();

        try {
            $details = $this->cloudflare->getTunnelDetails($tunnel);

            // Sync status to DB
            if ($details) {
                $tunnel->update([
                    'status' => $details['status'] ?? 'unknown',
                    'is_active' => ($details['status'] ?? '') === 'healthy',
                ]);
            }

            return response()->json([
                'status' => $details['status'] ?? 'unknown',
                'connections' => count($details['connections'] ?? []),
                'details' => $details,
                'tunnels' => $tunnels->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'status' => $t->status,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function verifyToken(Request $request)
    {
        $request->validate([
            'account_id' => 'required|string',
            'api_token' => 'required|string',
        ]);

        $valid = $this->cloudflare->verifyToken($request->api_token, $request->account_id);

        if (! $valid) {
            return response()->json(['message' => 'Invalid API Token'], 422);
        }

        $config = Cloudflare::firstOrNew();
        $config->account_id = $request->account_id;
        $config->api_token = $request->api_token;
        $config->save();

        return response()->json(['message' => 'Token verified', 'valid' => true]);
    }

    public function createTunnel(Request $request)
    {
        $account = Cloudflare::firstOrFail();
        $request->validate(['name' => 'nullable|string']);
        $name = $request->input('name', 'muraqib-node');

        try {
            $tunnelData = $this->cloudflare->findOrCreateTunnel($account, $name);

            // Create or update local Tunnel record
            $tunnel = $account->tunnels()->updateOrCreate(
                ['tunnel_id' => $tunnelData['id']],
                [
                    'name' => $tunnelData['name'],
                    'status' => 'created',
                ]
            );

            // Fetch token
            $token = $this->cloudflare->getTunnelToken($tunnel);
            $tunnel->token = $token;
            $tunnel->save();

            return response()->json([
                'tunnel_id' => $tunnel->tunnel_id,
                'tunnel_token' => $token,
                'name' => $tunnel->name,
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => 'Failed to create tunnel: '.$e->getMessage()], 500);
        }
    }

    public function listZones()
    {
        $config = Cloudflare::firstOrFail();
        try {
            $zones = $this->cloudflare->listZones($config->api_token);

            return response()->json($zones);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getDnsRecords(Request $request)
    {
        // This traditionally relied on stored zone_id on Account.
        // Now Zone ID is on Domain.
        // If request has domain_id or zone_id, use it.
        // Fallback: Check first domain?

        $request->validate(['zone_id' => 'nullable|string']);
        $zoneId = $request->zone_id;

        if (! $zoneId) {
            // Try to find from first domain
            $domain = \App\Models\CloudflareDomain::first();
            $zoneId = $domain?->zone_id;
        }

        if (! $zoneId) {
            return response()->json([]);
        }

        // We need a dummy domain object with zone_id to pass to service, or refactor service.
        // Service expects CloudflareDomain.
        // Let's find the domain model.
        $domain = \App\Models\CloudflareDomain::where('zone_id', $zoneId)->first();

        if (! $domain) {
            // If we are browsing zones not yet in DB? Service needs Cloudflare account via relationship.
            // We can hack it or fix service. Service uses: $domain->loadMissing('cloudflare').
            // We need a domain attached to an account.
            // If manual browsing, we might fail here.
            // But usually we list records for *configured* domains.
            return response()->json([]);
        }

        try {
            $records = $this->cloudflare->listDnsRecords($domain);

            return response()->json($records);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function updateIngress(Request $request)
    {
        $request->validate([
            'services' => 'required|array',
            'services.*.hostname' => 'required|string',
            'services.*.service' => 'required|string',
            'zone_id' => 'required|string',
            'tunnel_id' => 'nullable|string', // Cloudflare Tunnel ID (UUID)
        ]);

        $account = Cloudflare::firstOrFail();

        // Find Tunnel
        $tunnel = null;
        if ($request->tunnel_id) {
            $tunnel = $account->tunnels()->where('tunnel_id', $request->tunnel_id)->first();
        } else {
            $tunnel = $account->tunnels()->first();
        }

        if (! $tunnel) {
            return response()->json(['message' => 'No tunnel found'], 404);
        }

        try {
            // Update Configuration
            $this->cloudflare->updateIngressRules($tunnel, $request->services);

            // Handle Domain/DNS
            // Just assume one domain for now or create it based on zone_id
            $domain = $account->domains()->updateOrCreate(
                ['zone_id' => $request->zone_id],
                ['name' => 'Primary Domain', 'status' => 'active'] // Name is arbitrary if we don't know it from zone list
            );

            // Create DNS records for each
            foreach ($request->services as $svc) {
                // Determine subdomain.
                // Logic: full hostname "sub.example.com", zone "example.com" -> "sub"?
                // Or just pass full hostname to Cloudflare?
                // Service logic expects something. Service passes 'name' => $subdomain to API.
                // Cloudflare CNAME 'name' can be 'sub' or 'sub.example.com'.
                $this->cloudflare->createDnsRecord($domain, $tunnel, $svc['hostname']);
            }

            $tunnel->update(['is_active' => true, 'status' => 'healthy']);

            return response()->json(['message' => 'Tunnel configured successfully']);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => 'Ingress update failed: '.$e->getMessage()], 500);
        }
    }

    public function getIngress(Request $request)
    {
        $account = Cloudflare::first();
        if (! $account) {
            return response()->json([]);
        }

        // Find Tunnel
        $tunnel = null;
        if ($request->tunnel_id) {
            $tunnel = $account->tunnels()->where('tunnel_id', $request->tunnel_id)->first();
        } else {
            $tunnel = $account->tunnels()->first();
        }

        if (! $tunnel) {
            return response()->json([]);
        }

        try {
            $ingress = $this->cloudflare->getTunnelConfig($tunnel);

            // Filter out the 404 catch-all rule if present
            $filtered = collect($ingress)->filter(function ($rule) {
                return isset($rule['hostname']);
            })->values();

            return response()->json($filtered);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
