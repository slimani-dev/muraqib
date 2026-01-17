<?php

namespace Tests\Integration;

use App\Models\Portainer;
use App\Services\PortainerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Integration tests using REAL Portainer API
 * These tests hit the actual Portainer instance
 */
beforeEach(function () {
    // Create portainer with REAL credentials
    $this->portainer = Portainer::factory()->create([
        'name' => 'Production Portainer',
        'url' => 'https://portainer.example.com',
        'access_token' => 'ptr_test_token_placeholder_12345',
        'status' => 'active',
    ]);

    $this->service = new PortainerService($this->portainer);
});

it('connects to real portainer instance', function () {
    $connected = $this->service->checkConnection();

    expect($connected)->toBeTrue();
});

it('fetches real stacks from portainer api', function () {
    $stacks = $this->service->getStacks();

    expect($stacks->count())->toBeGreaterThan(0);
    expect($stacks->first())->toHaveProperties(['id', 'name', 'endpointId']);
});

it('fetches real containers from portainer api', function () {
    $containers = $this->service->getContainers();

    expect($containers->count())->toBeGreaterThan(0);
    expect($containers->first())->toHaveProperties(['id', 'name', 'image', 'state']);
});

it('gets real portainer version', function () {
    $version = $this->service->getVersion();

    expect($version)->not->toBeNull();
    expect($version)->toContain('.');
});

it('syncs real data to database via relation managers', function () {
    // This will actually sync from real API
    $this->service->getStacks()->each(function ($stackData) {
        \App\Models\Stack::updateOrCreate(
            [
                'portainer_id' => $this->portainer->id,
                'external_id' => (string) $stackData->id,
            ],
            [
                'name' => $stackData->name,
                'endpoint_id' => $stackData->endpointId,
                'stack_status' => $stackData->status,
                'stack_type' => $stackData->type,
                'created_at_portainer' => $stackData->createdAt,
            ]
        );
    });

    expect(\App\Models\Stack::count())->toBeGreaterThan(0);

    // Verify data integrity
    $firstStack = \App\Models\Stack::first();
    expect($firstStack->name)->not->toBeNull();
    expect($firstStack->endpoint_id)->toBeInt();
});

it('handles real api endpoints correctly', function () {
    $endpoints = $this->service->getEndpoints();

    expect($endpoints)->toBeArray();
    expect(count($endpoints))->toBeGreaterThan(0);
});
