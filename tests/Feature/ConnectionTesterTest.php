<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

test('test connection endpoint returns success on valid response', function () {
    $user = User::factory()->create();

    Http::fake([
        'portainer.local/api/endpoints' => Http::response([], 200),
    ]);

    $response = $this->actingAs($user)->postJson(route('settings.test-connection'), [
        'service' => 'portainer',
        'payload' => [
            'portainer_url' => 'https://portainer.local',
            'portainer_api_key' => 'valid-key',
        ],
    ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Connection successful.']);
});

test('test connection endpoint returns fail on invalid response', function () {
    $user = User::factory()->create();

    Http::fake([
        'portainer.local/api/endpoints' => Http::response([], 401),
    ]);

    $response = $this->actingAs($user)->postJson(route('settings.test-connection'), [
        'service' => 'portainer',
        'payload' => [
            'portainer_url' => 'https://portainer.local',
            'portainer_api_key' => 'invalid-key',
        ],
    ]);

    $response->assertStatus(400)
        ->assertJson(['message' => 'Connection failed.']);
});
