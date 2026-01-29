<?php

use App\Models\Cloudflare;
use App\Models\CloudflareDomain;
use App\Services\Cloudflare\CloudflareService;

/**
 * Manual verification test to delete an actual access token and verify via API
 * This test is intended to be run manually to verify real Cloudflare API integration
 */
it('can delete a duplicate muraqib-netdata token and verify removal', function () {
    // Find the cloudflare account
    $cloudflare = Cloudflare::first();
    
    if (!$cloudflare) {
        $this->markTestSkipped('No Cloudflare account found in database');
    }

    $service = new CloudflareService();

    // Get current tokens from Cloudflare API
    $tokensBefore = $service->listServiceTokens($cloudflare);
    $tokenCountBefore = count($tokensBefore);
    
    expect($tokenCountBefore)->toBeGreaterThan(0);

    // Find one of the duplicate "Muraqib-netdata" tokens (the older one from 2026-01-18T09:26:24Z)
    $targetTokenId = '93b8be05-5688-4286-869c-1f29bfbea4f7';
    
    // Check if this token exists in our database
    $accessToken = \App\Models\CloudflareAccess::where('client_id', $targetTokenId)->first();
    
    if (!$accessToken) {
        $this->markTestSkipped("Target token {$targetTokenId} not found in database");
    }

    dump("Before deletion: {$tokenCountBefore} tokens");
    dump("Deleting token: {$accessToken->name} (ID: {$accessToken->client_id})");

    // Delete using the service
    $domain = $accessToken->domain;
    expect($domain)->not->toBeNull();

    $service->deleteSubdomainProtection($domain, $accessToken);

    // Wait a moment for API propagation
    sleep(2);

    // Verify token is removed from Cloudflare
    $tokensAfter = $service->listServiceTokens($cloudflare);
    $tokenCountAfter = count($tokensAfter);
    
    dump("After deletion: {$tokenCountAfter} tokens");

    expect($tokenCountAfter)->toBe($tokenCountBefore - 1);

    // Verify the specific token is gone
    $tokenIds = collect($tokensAfter)->pluck('id')->toArray();
    expect($tokenIds)->not->toContain($targetTokenId);

    // Delete from database too
    $accessToken->delete();

    dump("✅ Successfully deleted token from both Cloudflare and database");
})->skip('Manual verification test - run explicitly when needed');
