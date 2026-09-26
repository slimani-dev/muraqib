<?php

use App\Models\Cloudflare;
use App\Services\Cloudflare\CloudflareService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

test('cloudflare access decoupled flow', function () {
    $accountId = env('CLOUDFLARE_TEST_ACCOUNT_ID');
    $apiToken = env('CLOUDFLARE_TEST_API_TOKEN');

    if (empty($accountId) || empty($apiToken)) {
        $this->markTestSkipped('Cloudflare credentials (CLOUDFLARE_TEST_ACCOUNT_ID, CLOUDFLARE_TEST_API_TOKEN) not set in .env.testing');
    }

    $account = Cloudflare::factory()->create([
        'account_id' => $accountId,
        'api_token' => $apiToken,
        'name' => 'Integration Test Account',
    ]);

    $service = app(CloudflareService::class);
    $timestamp = time();
    $tokenName = "Test-Token-$timestamp";

    // 1. Create Service Token
    try {
        $tokenRes = $service->createServiceToken($account, $tokenName);
    } catch (Exception $e) {
        $this->fail('Failed to create service token: '.$e->getMessage());
    }

    expect($tokenRes)->toHaveKeys(['id', 'client_id', 'client_secret', 'name']);
    expect($tokenRes['name'])->toBe($tokenName);

    // Persist to DB
    $dbToken = $account->serviceTokens()->create([
        'token_id' => $tokenRes['id'],
        'name' => $tokenRes['name'],
        'client_id' => $tokenRes['client_id'],
        'client_secret' => $tokenRes['client_secret'],
    ]);

    assertDatabaseHas('cloudflare_service_tokens', [
        'id' => $dbToken->id,
        'token_id' => $tokenRes['id'],
    ]);

    // 2. Create Access Application
    // Need a valid zone.
    try {
        $zones = $service->listZones($apiToken);
    } catch (Exception $e) {
        // Cleanup token before failing
        $service->deleteServiceToken($account, $tokenRes['id']);
        $this->fail('Failed to list zones: '.$e->getMessage());
    }

    if (empty($zones)) {
        $service->deleteServiceToken($account, $tokenRes['id']);
        $this->markTestSkipped('No zones found in Cloudflare account.');
    }

    $zone = $zones[0];
    $appDomain = "test-access-$timestamp.".$zone['name'];
    $appName = "Test App $timestamp";

    try {
        $appRes = $service->createAccessApplication($account, $appName, $appDomain);
    } catch (Exception $e) {
        $service->deleteServiceToken($account, $tokenRes['id']);
        $this->fail('Failed to create access app: '.$e->getMessage());
    }

    expect($appRes)->toHaveKeys(['id', 'domain']);
    expect($appRes['domain'])->toBe($appDomain);

    $dbApp = $account->accessApplications()->create([
        'app_id' => $appRes['id'],
        'name' => $appRes['name'],
        'domain' => $appRes['domain'],
        'type' => $appRes['type'],
    ]);

    // 3. Create Access Policy attached to Service Token
    $policyName = "Test Policy $timestamp";
    // Construct include array
    $include = [['service_token' => ['token_id' => $tokenRes['id']]]];

    try {
        $policyRes = $service->createAccessPolicy($account, $appRes['id'], $policyName, 'non_identity', $include);
    } catch (Exception $e) {
        // Cleanup App and Token
        $service->deleteAccessApplication($account, $appRes['id']);
        $service->deleteServiceToken($account, $tokenRes['id']);
        $this->fail('Failed to create policy: '.$e->getMessage());
    }

    expect($policyRes)->toHaveKey('id');
    expect($policyRes['decision'])->toBe('non_identity');

    $dbPolicy = $dbApp->policies()->create([
        'policy_id' => $policyRes['id'],
        'name' => $policyRes['name'],
        'decision' => $policyRes['decision'],
    ]);

    // Attach pivot
    $dbPolicy->serviceTokens()->attach($dbToken->id);

    // Verify Pivot
    $this->assertDatabaseHas('cloudflare_access_policy_service_token', [
        'policy_id' => $dbPolicy->id,
        'service_token_id' => $dbToken->id,
    ]);

    // Cleanup (Reverse Order)
    // Delete Policy
    $delPolicy = $service->deleteAccessPolicy($account, $appRes['id'], $policyRes['id']);
    expect($delPolicy)->toBeTrue();

    // Delete App
    $delApp = $service->deleteAccessApplication($account, $appRes['id']);
    expect($delApp)->toBeTrue();

    // Delete Token
    $delToken = $service->deleteServiceToken($account, $tokenRes['id']);
    expect($delToken)->toBeTrue();

});
