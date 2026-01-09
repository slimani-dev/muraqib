<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ConnectionTesterController extends Controller
{
    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'service' => ['required', 'string', 'in:portainer,proxmox,cloudflare,jellyfin,jellyseerr,transmission'],
            'payload' => ['required', 'array'],
        ]);

        $service = $request->input('service');
        $payload = $request->input('payload');

        try {
            $success = match ($service) {
                'portainer' => $this->testPortainer($payload),
                'proxmox' => $this->testProxmox($payload),
                'cloudflare' => $this->testCloudflare($payload),
                'jellyfin' => $this->testJellyfin($payload),
                'jellyseerr' => $this->testJellyseerr($payload),
                'transmission' => $this->testTransmission($payload),
                default => false,
            };

            if ($success) {
                return response()->json(['message' => 'Connection successful.'], 200);
            }

            return response()->json(['message' => 'Connection failed.'], 400);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Connection error: '.$e->getMessage()], 500);
        }
    }

    private function testPortainer(array $payload): bool
    {
        $url = rtrim($payload['portainer_url'], '/');
        $apiKey = $payload['portainer_api_key'] ?? null;

        if (! $apiKey) {
            // If generic settings usage, retrieve from settings if not sending password?
            // But usually testing un-saved changes.
            // If empty, user might be testing existing config? or invalid.
            return false;
        }

        $response = Http::withHeaders(['X-API-Key' => $apiKey])
            ->timeout(5)
            ->get("{$url}/api/endpoints");

        return $response->successful();
    }

    private function testProxmox(array $payload): bool
    {
        $url = rtrim($payload['proxmox_url'], '/');
        // Proxmox API requires Authorization header: PVEAPIToken=USER@REALM!TOKENID=UUID
        // Or Cookie, but here we use token.
        $user = $payload['proxmox_user'];
        $tokenId = $payload['proxmox_token_id'];
        $secret = $payload['proxmox_secret'] ?? null;

        if (! $secret) {
            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => "PVEAPIToken={$user}!{$tokenId}={$secret}",
        ])
            ->withoutVerifying() // Proxmox often self-signed
            ->timeout(5)
            ->get("{$url}/api2/json/version");

        return $response->successful();
    }

    private function testCloudflare(array $payload): bool
    {
        $token = $payload['cloudflare_api_token'] ?? null;
        if (! $token) {
            return false;
        }

        $response = Http::withToken($token)
            ->timeout(5)
            ->get('https://api.cloudflare.com/client/v4/user/tokens/verify');

        return $response->successful() && $response->json('result.status') === 'active';
    }

    private function testJellyfin(array $payload): bool
    {
        $url = rtrim($payload['jellyfin_url'], '/');
        $key = $payload['jellyfin_api_key'] ?? null;

        if (! $key) {
            return false;
        }

        // Typically /System/Info or /Sessions
        $response = Http::withHeaders([
            'X-Emby-Token' => $key,
        ])
            ->timeout(5)
            ->get("{$url}/System/Info");

        return $response->successful();
    }

    private function testJellyseerr(array $payload): bool
    {
        $url = rtrim($payload['jellyseerr_url'], '/');
        $key = $payload['jellyseerr_api_key'] ?? null;

        if (! $key) {
            return false;
        }

        $response = Http::withHeaders([
            'X-Api-Key' => $key,
        ])
            ->timeout(5)
            ->get("{$url}/api/v1/status");

        return $response->successful();
    }

    private function testTransmission(array $payload): bool
    {
        $url = rtrim($payload['transmission_url'], '/'); // RPC URL
        $user = $payload['transmission_username'];
        $pass = $payload['transmission_password'] ?? null;

        if (! $pass) {
            return false;
        }

        // Transmission requires a Session ID first, usually returns 409 with the ID in header.
        $response = Http::withBasicAuth($user, $pass)
            ->timeout(5)
            ->post($url, [
                'method' => 'session-get',
            ]);

        if ($response->status() === 409) {
            $sessionId = $response->header('X-Transmission-Session-Id');
            if ($sessionId) {
                // Retry with session ID
                $response = Http::withBasicAuth($user, $pass)
                    ->withHeaders(['X-Transmission-Session-Id' => $sessionId])
                    ->timeout(5)
                    ->post($url, [
                        'method' => 'session-get',
                    ]);

                return $response->successful();
            }
        }

        return $response->successful();
    }
}
