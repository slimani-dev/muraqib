<?php

use App\Data\ContainerData;
use App\Data\StackData;
use App\Models\Portainer;
use App\Services\PortainerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->portainer = Portainer::factory()->create([
        'name' => 'Test Portainer',
        'url' => 'https://portainer.example.com',
        'access_token' => 'ptr_test_token_placeholder_12345',
        'status' => 'active',
    ]);

    $this->service = new PortainerService($this->portainer);
});

it('can check portainer connection', function () {
    Http::fake([
        '*/api/system/status' => Http::response(['Version' => '2.19.4'], 200),
    ]);

    $result = $this->service->checkConnection();

    expect($result)->toBeTrue();
});

it('returns false on connection failure', function () {
    Http::fake([
        '*/api/system/status' => Http::response([], 500),
    ]);

    $result = $this->service->checkConnection();

    expect($result)->toBeFalse();
});

it('can get system info', function () {
    Http::fake([
        '*/api/system/status' => Http::response([
            'Version' => '2.19.4',
            'Edition' => 'Community Edition',
        ], 200),
    ]);

    $info = $this->service->getSystemInfo();

    expect($info)
        ->toBeArray()
        ->toHaveKey('Version', '2.19.4')
        ->toHaveKey('Edition', 'Community Edition');
});

it('can get portainer version', function () {
    Http::fake([
        '*/api/system/status' => Http::response(['Version' => '2.19.4'], 200),
    ]);

    $version = $this->service->getVersion();

    expect($version)->toBe('2.19.4');
});

it('can get endpoints', function () {
    Http::fake([
        '*/api/endpoints' => Http::response([
            ['Id' => 1, 'Name' => 'local', 'Type' => 1],
            ['Id' => 2, 'Name' => 'remote', 'Type' => 2],
        ], 200),
    ]);

    $endpoints = $this->service->getEndpoints();

    expect($endpoints)
        ->toBeArray()
        ->toHaveCount(2)
        ->and($endpoints[0])->toHaveKey('Id', 1);
});

it('can get stacks', function () {
    Http::fake([
        '*/api/stacks' => Http::response([
            [
                'Id' => 1,
                'Name' => 'test-stack',
                'EndpointId' => 1,
                'Status' => 'active',
                'Type' => 2,
            ],
        ], 200),
    ]);

    $stacks = $this->service->getStacks();

    expect($stacks)
        ->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->toHaveCount(1)
        ->and($stacks->first())
        ->toBeInstanceOf(StackData::class)
        ->and($stacks->first()->name)->toBe('test-stack');
});

it('can get containers', function () {
    Http::fake([
        '*/api/endpoints' => Http::response([['Id' => 1]], 200),
        '*/api/endpoints/*/docker/containers/json*' => Http::response([
            [
                'Id' => 'abc123',
                'Names' => ['/test-container'],
                'Image' => 'nginx:latest',
                'State' => 'running',
                'Status' => 'Up 2 days',
                'Labels' => ['com.docker.compose.project' => 'test-stack'],
            ],
        ], 200),
    ]);

    $containers = $this->service->getContainers();

    expect($containers)
        ->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->toHaveCount(1)
        ->and($containers->first())
        ->toBeInstanceOf(ContainerData::class)
        ->and($containers->first()->name)->toBe('test-container')
        ->and($containers->first()->stackName)->toBe('test-stack');
});

it('can get stack containers', function () {
    Http::fake([
        '*/api/endpoints' => Http::response([['Id' => 1]], 200),
        '*/api/endpoints/*/docker/containers/json*' => Http::response([
            [
                'Id' => 'abc123',
                'Names' => ['/test-container'],
                'Image' => 'nginx:latest',
                'State' => 'running',
                'Status' => 'Up 2 days',
                'Labels' => ['com.docker.compose.project' => 'test-stack'],
            ],
            [
                'Id' => 'def456',
                'Names' => ['/other-container'],
                'Image' => 'redis:latest',
                'State' => 'running',
                'Status' => 'Up 1 day',
                'Labels' => ['com.docker.compose.project' => 'other-stack'],
            ],
        ], 200),
    ]);

    $containers = $this->service->getStackContainers('test-stack');

    expect($containers)
        ->toHaveCount(1)
        ->and($containers->first()->name)->toBe('test-container');
});

it('can sync portainer info', function () {
    Http::fake([
        '*/api/system/status' => Http::response([
            'Version' => '2.19.4',
            'Edition' => 'Community Edition',
        ], 200),
    ]);

    $this->service->sync();

    $this->portainer->refresh();

    expect($this->portainer->version)->toBe('2.19.4')
        ->and($this->portainer->last_synced_at)->not->toBeNull();
});

it('can check for updates', function () {
    Http::fake([
        '*/api/system/status' => Http::response(['Version' => '2.19.4'], 200),
        'https://api.github.com/repos/portainer/portainer/releases/latest' => Http::response([
            'tag_name' => 'v2.20.0',
            'html_url' => 'https://github.com/portainer/portainer/releases/tag/v2.20.0',
        ], 200),
    ]);

    $updateInfo = $this->service->checkForUpdates();

    expect($updateInfo)
        ->toBeArray()
        ->toHaveKey('current', '2.19.4')
        ->toHaveKey('latest', '2.20.0')
        ->toHaveKey('update_available', true);
});

it('can get statistics', function () {
    Http::fake([
        '*/api/endpoints' => Http::response([
            ['Id' => 1, 'Name' => 'local'],
            ['Id' => 2, 'Name' => 'remote'],
        ], 200),
        '*/api/stacks' => Http::response([
            ['Id' => 1, 'Name' => 'stack1', 'EndpointId' => 1, 'Status' => 'active'],
            ['Id' => 2, 'Name' => 'stack2', 'EndpointId' => 1, 'Status' => 'active'],
        ], 200),
        '*/api/endpoints/*/docker/containers/json*' => Http::response([
            ['Id' => 'abc', 'Names' => ['/c1'], 'Image' => 'nginx', 'State' => 'running', 'Status' => 'Up'],
            ['Id' => 'def', 'Names' => ['/c2'], 'Image' => 'redis', 'State' => 'exited', 'Status' => 'Exited'],
            ['Id' => 'ghi', 'Names' => ['/c3'], 'Image' => 'mysql', 'State' => 'running', 'Status' => 'Up'],
        ], 200),
    ]);

    $stats = $this->service->getStats();

    expect($stats)
        ->toBeArray()
        ->toHaveKey('endpoints_count', 2)
        ->toHaveKey('stacks_count', 2)
        ->toHaveKey('containers_count', 3)
        ->toHaveKey('containers_running', 2);
});
