<?php

use App\Models\Portainer;
use App\Services\PortainerService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('returns true when connection is successful', function () {
    $portainer = new Portainer(['url' => 'http://test.local', 'access_token' => 'token']);

    Http::fake([
        'http://test.local/api/system/status' => Http::response([], 200),
    ]);

    $service = new PortainerService($portainer);
    expect($service->checkConnection())->toBeTrue();
});

it('returns false when connection fails', function () {
    $portainer = new Portainer(['url' => 'http://test.local', 'access_token' => 'token']);

    Http::fake([
        'http://test.local/api/system/status' => Http::response([], 500),
    ]);

    $service = new PortainerService($portainer);
    expect($service->checkConnection())->toBeFalse();
});

it('returns false when exception occurs', function () {
    $portainer = new Portainer(['url' => 'http://test.local', 'access_token' => 'token']);

    Http::fake(function () {
        throw new \Exception('Network error');
    });

    $service = new PortainerService($portainer);
    expect($service->checkConnection())->toBeFalse();
});
