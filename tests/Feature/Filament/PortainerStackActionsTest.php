<?php

use App\Filament\Resources\Portainers\RelationManagers\StacksRelationManager;
use App\Models\Portainer;
use App\Models\Stack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->portainer = Portainer::factory()->create([
        'url' => 'http://portainer.test',
        'access_token' => 'test-token',
    ]);
});

it('cannot delete running stack', function () {
    $stack = Stack::create([
        'portainer_id' => $this->portainer->id,
        'external_id' => '1',
        'name' => 'running-stack',
        'endpoint_id' => 1,
        'stack_status' => 1, // Running
        'stack_type' => 2,
    ]);

    Livewire::test(StacksRelationManager::class, [
        'ownerRecord' => $this->portainer,
        'pageClass' => 'App\Filament\Resources\Portainers\Pages\ViewPortainer',
    ])
        ->assertTableActionHidden('delete', $stack);
});

it('can delete stopped stack', function () {
    $stack = Stack::create([
        'portainer_id' => $this->portainer->id,
        'external_id' => '2',
        'name' => 'stopped-stack',
        'endpoint_id' => 1,
        'stack_status' => 2, // Stopped
        'stack_type' => 2,
    ]);

    Http::fake([
        '*/api/stacks/2?endpointId=1' => Http::response([], 200),
    ]);

    Livewire::test(StacksRelationManager::class, [
        'ownerRecord' => $this->portainer,
        'pageClass' => 'App\Filament\Resources\Portainers\Pages\ViewPortainer',
    ])
        ->callTableAction('delete', $stack);

    expect(Stack::find($stack->id))->toBeNull();
});

it('can bulk start stopped stacks', function () {
    $stoppedStack = Stack::create([
        'portainer_id' => $this->portainer->id,
        'external_id' => '3',
        'name' => 'stopped-stack-1',
        'endpoint_id' => 1,
        'stack_status' => 2, // Stopped
        'stack_type' => 2,
    ]);

    $runningStack = Stack::create([
        'portainer_id' => $this->portainer->id,
        'external_id' => '4',
        'name' => 'running-stack-1',
        'endpoint_id' => 1,
        'stack_status' => 1, // Running
        'stack_type' => 2,
    ]);

    Http::fake([
        '*/api/stacks/3/start?endpointId=1' => Http::response([], 200),
    ]);

    Livewire::test(StacksRelationManager::class, [
        'ownerRecord' => $this->portainer,
        'pageClass' => 'App\Filament\Resources\Portainers\Pages\ViewPortainer',
    ])
        ->callTableBulkAction('start', [$stoppedStack, $runningStack]);

    // Verify only stopped stack API call was made
    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/api/stacks/3/start');
    });

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), '/api/stacks/4/start');
    });
});
