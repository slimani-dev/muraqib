<?php

use App\Models\Cloudflare;
use App\Models\CloudflareDomain;
use App\Services\Cloudflare\CloudflareService;
use Illuminate\Support\Facades\Http;

it('creates protection resources successfully', function () {
    Http::fake([
        '*/access/service_tokens' => Http::response(['result' => ['id' => 'token_id', 'client_id' => 'cid', 'client_secret' => 'sec']], 200),
        '*/access/apps' => Http::response(['result' => ['id' => 'app_id']], 200),
        '*/policies' => Http::response(['result' => ['id' => 'policy_id']], 200),
    ]);

    $account = Cloudflare::factory()->create(['api_token' => 'fake_token', 'account_id' => 'fake_account']);
    $domain = CloudflareDomain::factory()->create(['cloudflare_id' => $account->id]);

    $service = app(CloudflareService::class);
    $result = $service->protectSubdomain($domain, 'protected.example.com');

    expect($result)->toBeInstanceOf(\App\Models\CloudflareAccess::class)
        ->and($result->client_id)->toBe('cid');

    // Verify Requests
    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/access/apps')) {
            return false;
        }

        return isset($request['session_duration']) &&
               $request['session_duration'] === '24h' &&
               $request['domain'] === 'protected.example.com';
    });

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/policies')) {
            return false;
        }

        return $request['decision'] === 'non_identity';
    });
});

it('throws exception with api error message on failure', function () {
    Http::fake([
        '*/access/service_tokens' => Http::response(['result' => ['id' => 'token_id']], 200),
        '*/access/apps' => Http::response([
            'success' => false,
            'errors' => [['message' => 'domain does not belong to zone']],
        ], 400),
    ]);

    $account = Cloudflare::factory()->create(['api_token' => 'fake_token', 'account_id' => 'fake_account']);
    $domain = CloudflareDomain::factory()->create(['cloudflare_id' => $account->id]);

    $service = app(CloudflareService::class);

    try {
        $service->protectSubdomain($domain, 'bad.example.com');
    } catch (Exception $e) {
        expect($e->getMessage())->toContain('domain does not belong to zone');

        return;
    }

    $this->fail('Exception was not thrown');
});

it('reuses existing application if already exists', function () {
    Http::fake([
        '*/access/service_tokens' => Http::response(['result' => ['id' => 'token_id', 'client_id' => 'cid', 'client_secret' => 'sec']], 200),
        '*/access/apps' => function (\Illuminate\Http\Client\Request $request) {
            // Mock failing POST (create)
            if ($request->method() === 'POST' && str_contains($request->url(), '/apps') && ! str_contains($request->url(), '/apps/')) {
                return Http::response([
                    'success' => false,
                    'errors' => [['message' => 'access.api.error.application_already_exists']],
                ], 400);
            }
            // Mock GET (list)
            if ($request->method() === 'GET') {
                return Http::response(['result' => [
                    ['id' => 'existing_app_id', 'domain' => 'protected.example.com'],
                ]], 200);
            }

            return Http::response(['result' => []], 200);
        },
        '*/policies' => Http::response(['result' => ['id' => 'policy_id']], 200),
    ]);

    $account = Cloudflare::factory()->create(['api_token' => 'fake_token', 'account_id' => 'fake_account']);
    $domain = CloudflareDomain::factory()->create(['cloudflare_id' => $account->id]);

    $service = app(CloudflareService::class);
    $result = $service->protectSubdomain($domain, 'protected.example.com');

    expect($result)->toBeInstanceOf(\App\Models\CloudflareAccess::class)
        ->and($result->app_id)->toBe('existing_app_id');
});
