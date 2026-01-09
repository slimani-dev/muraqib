<?php

use App\Services\Portainer\PortainerClient;
use Illuminate\Support\Facades\Http;

it('returns connected status when portainer is reachable', function () {
    // Mock the PortainerClient
    $this->mock(PortainerClient::class, function ($mock) {
        $mock->shouldReceive('getEndpoints')
            ->once()
            ->andReturn(collect([]));
    });

    $response = $this->get('/api/portainer/status');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'connected',
        ])
        ->assertJsonStructure(['latency_ms']);
});

it('returns error status when portainer is unreachable', function () {
    // Mock the PortainerClient to throw exception
    $this->mock(PortainerClient::class, function ($mock) {
        $mock->shouldReceive('getEndpoints')
            ->once()
            ->andThrow(new \Exception('Connection refused'));
    });

    $response = $this->get('/api/portainer/status');

    $response->assertStatus(500)
        ->assertJson([
            'status' => 'error',
            'message' => 'Connection refused',
        ]);
});
