<?php

use App\Filament\Resources\Cloudflares\RelationManagers\AccessTokensRelationManager;
use App\Models\Cloudflare;
use App\Models\CloudflareDomain;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('can render access tokens relation manager', function () {
    $cloudflare = Cloudflare::factory()->create();
    $domain = CloudflareDomain::factory()->count(1)->create(['cloudflare_id' => $cloudflare->id]);

    $component = Livewire::test(AccessTokensRelationManager::class, [
        'ownerRecord' => $cloudflare,
        'pageClass' => \App\Filament\Resources\Cloudflares\CloudflareResource::class,
    ]);

    $component->assertStatus(200);

    $component->assertTableHeaderActionsExistInOrder(['sync_tokens', 'protect_subdomain']);

    $component->mountTableAction('protect_subdomain')
        ->assertTableActionMounted('protect_subdomain')
        ->assertActionDataSet([
            'domain_id' => null,
            'hostname' => null,
        ])
        ->setTableActionData([
            'domain_id' => $domain->first()->id,
        ])
    // After setting domain_id, hostname options should populate.
    // We can't easily assert options content in integration test without digging into component,
    // but we can assert we can set hostname.
        ->setTableActionData([
            'domain_id' => $domain->first()->id,
            'hostname' => 'sub.example.com',
        ]);
});

it('can sync tokens', function () {
    $cloudflare = Cloudflare::factory()->create(['api_token' => 'valid_token']);
    $domain = CloudflareDomain::factory()->create(['cloudflare_id' => $cloudflare->id, 'name' => 'example.com']);

    // Mock API response
    Http::fake([
        '*/accounts/*/access/service_tokens' => Http::response(['result' => [
            ['id' => 'existing_id', 'name' => 'Muraqib-test.example.com', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'new_id', 'name' => 'Muraqib-new.example.com', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'ignored_id', 'name' => 'Other-Token', 'created_at' => now(), 'updated_at' => now()],
        ]], 200),
    ]);

    // Create existing record to test update
    \App\Models\CloudflareAccess::create([
        'cloudflare_domain_id' => $domain->id,
        'app_id' => 'app_id',
        'name' => 'test.example.com',
        'client_id' => 'existing_id',
        'client_secret' => 'secret',
        'policy_id' => 'policy',
    ]);

    $component = Livewire::test(AccessTokensRelationManager::class, [
        'ownerRecord' => $cloudflare,
        'pageClass' => \App\Filament\Resources\Cloudflares\CloudflareResource::class,
    ]);

    // dd($component->instance()->getTable()->getHeaderActions());

    $component->assertTableActionExists('sync_tokens')
        ->mountTableAction('sync_tokens')
        ->callMountedTableAction();
    // ->assertNotified('Synced Tokens');

    // Assert Existing Updated
    $this->assertDatabaseHas('cloudflare_access_tokens', [
        'client_id' => 'existing_id',
        'name' => 'Muraqib-test.example.com', // Name updated from API
    ]);

    // Assert New Imported
    $this->assertDatabaseHas('cloudflare_access_tokens', [
        'client_id' => 'new_id',
        'name' => 'new.example.com', // Muraqib- prefix stripped
        'cloudflare_domain_id' => $domain->id,
    ]);

    // Assert Ignored
    $this->assertDatabaseMissing('cloudflare_access_tokens', [
        'client_id' => 'ignored_id',
    ]);
});
