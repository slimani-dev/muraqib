<?php

use App\Filament\Resources\Portainers\Pages\ViewPortainer;
use App\Filament\Resources\Portainers\RelationManagers\ContainersRelationManager;
use App\Filament\Resources\Portainers\RelationManagers\StacksRelationManager;
use App\Models\Portainer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->portainer = Portainer::factory()->create([
        'name' => 'Test Portainer',
        'url' => 'https://portainer.slimani.dev',
        'access_token' => 'ptr_test',
        'status' => 'active',
    ]);
});

it('syncs and displays stacks from portainer api', function () {
    // Fake the API response
    Http::fake([
        '*/api/stacks' => Http::response([
            [
                'Id' => 1,
                'Name' => 'test-stack',
                'EndpointId' => 2,
                'Status' => 1,
                'Type' => 2,
            ],
            [
                'Id' => 2,
                'Name' => 'production-stack',
                'EndpointId' => 2,
                'Status' => 1,
                'Type' => 2,
            ],
        ], 200),
    ]);

    // Test the relation manager renders with synced data
    $component = Livewire::test(StacksRelationManager::class, [
        'ownerRecord' => $this->portainer,
        'pageClass' => ViewPortainer::class,
    ]);

    // Should see the synced database data
    $component->assertSee('test-stack')
        ->assertSee('production-stack');

    // Verify data was saved to database
    expect($this->portainer->stacks()->count())->toBe(2);
    expect($this->portainer->stacks()->where('name', 'test-stack')->exists())->toBeTrue();
});

it('syncs and displays containers from portainer api', function () {
    // Fake the API response
    Http::fake([
        '*/api/endpoints' => Http::response([['Id' => 2]], 200),
        '*/api/endpoints/*/docker/containers/json*' => Http::response([
            [
                'Id' => 'abc123',
                'Names' => ['/nginx'],
                'Image' => 'nginx:latest',
                'State' => 'running',
                'Status' => 'Up 2 hours',
            ],
            [
                'Id' => 'def456',
                'Names' => ['/redis'],
                'Image' => 'redis:alpine',
                'State' => 'running',
                'Status' => 'Up 1 day',
            ],
        ], 200),
    ]);

    // Test the relation manager renders with synced data
    $component = Livewire::test(ContainersRelationManager::class, [
        'ownerRecord' => $this->portainer,
        'pageClass' => ViewPortainer::class,
    ]);

    // Should see the synced database data
    $component->assertSee('nginx')
        ->assertSee('redis')
        ->assertSee('nginx:latest')
        ->assertSee('redis:alpine');

    // Verify data was saved to database
    expect($this->portainer->containers()->count())->toBe(2);
    expect($this->portainer->containers()->where('name', 'nginx')->exists())->toBeTrue();
});

it('removes stacks from database when they disappear from api', function () {
    // First sync with 2 stacks
    Http::fake([
        '*/api/stacks' => Http::response([
            ['Id' => 1, 'Name' => 'stack-1', 'EndpointId' => 2, 'Status' => 1, 'Type' => 2],
            ['Id' => 2, 'Name' => 'stack-2', 'EndpointId' => 2, 'Status' => 1, 'Type' => 2],
        ], 200),
    ]);

    Livewire::test(StacksRelationManager::class, [
        'ownerRecord' => $this->portainer,
        'pageClass' => ViewPortainer::class,
    ]);

    expect($this->portainer->stacks()->count())->toBe(2);

    // Now API returns only 1 stack
    Http::fake([
        '*/api/stacks' => Http::response([
            ['Id' => 1, 'Name' => 'stack-1', 'EndpointId' => 2, 'Status' => 1, 'Type' => 2],
        ], 200),
    ]);

    Livewire::test(StacksRelationManager::class, [
        'ownerRecord' => $this->portainer,
        'pageClass' => ViewPortainer::class,
    ]);

    $this->portainer->refresh(); // Refresh to see updated relationship

    // Should now only have 1 stack
    expect($this->portainer->stacks()->count())->toBe(1);
    expect($this->portainer->stacks()->where('name', 'stack-1')->exists())->toBeTrue();
    expect($this->portainer->stacks()->where('name', 'stack-2')->exists())->toBeFalse();
});

it('updates existing stacks when api data changes', function () {
    // First sync
    Http::fake([
        '*/api/stacks' => Http::response([
            ['Id' => 1, 'Name' => 'old-name', 'EndpointId' => 2, 'Status' => 1, 'Type' => 2],
        ], 200),
    ]);

    Livewire::test(StacksRelationManager::class, [
        'ownerRecord' => $this->portainer,
        'pageClass' => ViewPortainer::class,
    ]);

    $this->portainer->refresh();
    expect($this->portainer->stacks()->first()->name)->toBe('old-name');

    // Name changed in API
    Http::fake([
        '*/api/stacks' => Http::response([
            ['Id' => 1, 'Name' => 'new-name', 'EndpointId' => 2, 'Status' => 1, 'Type' => 2],
        ], 200),
    ]);

    Livewire::test(StacksRelationManager::class, [
        'ownerRecord' => $this->portainer,
        'pageClass' => ViewPortainer::class,
    ]);

    $this->portainer->refresh(); // Refresh to see updated data

    // Should have updated the name
    expect($this->portainer->stacks()->count())->toBe(1);
    expect($this->portainer->stacks()->first()->name)->toBe('new-name');
});
