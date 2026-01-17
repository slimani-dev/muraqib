<?php

use App\Models\Portainer;
use App\Models\Stack;
use App\Services\PortainerService;
use Illuminate\Support\Facades\Http;

it('synes portainer system info', function () {
    Http::fake([
        '*/api/system/info' => Http::response(['platform' => 'linux', 'Version' => '2.19.4']),
        '*/api/stacks' => Http::response([]),
        '*/api/endpoints/1/docker/containers/json?all=1' => Http::response([]),
    ]);

    $portainer = Portainer::factory()->create([
        'url' => 'http://portainer.local',
        'access_token' => 'test-token',
    ]);

    $service = new PortainerService($portainer);
    $service->sync();

    $portainer->refresh();

    expect($portainer->version)->toBe('linux');
    expect($portainer->last_synced_at)->not->toBeNull();
});

it('synes portainer stacks', function () {
    Http::fake([
        '*/api/stacks' => Http::response([
            ['Name' => 'stack-1', 'Status' => 1],
            ['Name' => 'stack-2', 'Status' => 2],
        ]),
    ]);

    $portainer = Portainer::factory()->create([
        'url' => 'http://portainer.local',
        'access_token' => 'test-token',
    ]);

    $service = new PortainerService($portainer);
    $service->syncStacks();

    expect(Stack::count())->toBe(2);
    expect(Stack::where('name', 'stack-1')->exists())->toBeTrue();
});

it('synes portainer containers and links to stacks', function () {
    Http::fake([
        '*/api/endpoints/1/docker/containers/json?all=1' => Http::response([
            [
                'Id' => 'container-1',
                'Names' => ['/web-app'],
                'Image' => 'nginx:latest',
                'State' => 'running',
                'Labels' => [
                    'com.docker.compose.project' => 'my-stack',
                ],
            ],
            [
                'Id' => 'container-2',
                'Names' => ['/db'],
                'Image' => 'mysql:8.0',
                'State' => 'running',
                'Labels' => [],
            ],
        ]),
    ]);

    $portainer = Portainer::factory()->create([
        'url' => 'http://portainer.local',
        'access_token' => 'test-token',
    ]);

    // Create the stack beforehand to test linking
    $stack = Stack::create([
        'portainer_id' => $portainer->id,
        'name' => 'my-stack',
        'status' => 'active',
    ]);

    $service = new PortainerService($portainer);
    $service->syncContainers();

    expect(\App\Models\Container::count())->toBe(2);

    $container1 = \App\Models\Container::where('name', 'web-app')->first();
    expect($container1->stack_id)->toBe($stack->id);
    expect($container1->image)->toBe('nginx:latest');

    $container2 = \App\Models\Container::where('name', 'db')->first();
    expect($container2->stack_id)->toBeNull();
});
