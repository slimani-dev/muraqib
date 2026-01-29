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

    $this->stack = Stack::create([
        'portainer_id' => $this->portainer->id,
        'external_id' => '123',
        'name' => 'test-stack',
        'endpoint_id' => 1,
        'stack_status' => 1,
        'stack_type' => 2,
        'stack_file_content' => 'initial content',
    ]);
});

it('syncs stack data on edit modal open', function () {
    Http::fake([
        '*/api/stacks/123' => Http::response([
            'Id' => 123,
            'Name' => 'test-stack',
            'Env' => [['name' => 'NEW_VAR', 'value' => 'new_val']],
        ], 200),
        '*/api/stacks/123/file' => Http::response([
            'StackFileContent' => 'version: "3"\nservices:\n  web:\n    image: nginx',
        ], 200),
    ]);

    Livewire::test(StacksRelationManager::class, [
        'ownerRecord' => $this->portainer,
        'pageClass' => 'App\Filament\Resources\Portainers\Pages\ViewPortainer',
    ])
        ->mountTableAction('edit', $this->stack)
        ->assertFormSet([
            'prune' => false,
        ]);

    $this->stack->refresh();
    // Verify DB was updated
    expect($this->stack->env)->toBe([['name' => 'NEW_VAR', 'value' => 'new_val']]);
});

it('sends pull_image and prune flags when updating stack', function () {
    Http::fake([
        '*/api/stacks/123' => Http::response(['Id' => 123, 'Env' => []], 200),
        '*/api/stacks/123/file' => Http::response(['StackFileContent' => ''], 200),
        '*/api/stacks/123?endpointId=1' => Http::response([], 200),
    ]);

    Livewire::test(StacksRelationManager::class, [
        'ownerRecord' => $this->portainer,
        'pageClass' => 'App\Filament\Resources\Portainers\Pages\ViewPortainer',
    ])
        ->callTableAction('edit', $this->stack, [
            'stack_file_content' => 'updated content',
            'env' => [['name' => 'FOO', 'value' => 'BAR']],
            'redeploy' => true,
            'prune' => true,
            'pull_image' => true,
        ])
        ->assertHasNoTableActionErrors();

    // Verify the HTTP request was made with correct params
    Http::assertSent(function ($request) {
        return $request->method() === 'PUT' &&
               $request->url() === 'http://portainer.test/api/stacks/123?endpointId=1' &&
               $request['prune'] === true &&
               $request['pullImage'] === true &&
               $request['stackFileContent'] === 'updated content';
    });
});
