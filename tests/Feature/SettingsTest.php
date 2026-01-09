<?php

use App\Models\User;
use App\Settings\GeneralSettings;

test('settings redirect to general', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('settings.redirect'));

    $response->assertRedirect(route('settings.general'));
});

test('general settings page can be rendered with password confirmation', function () {
    $user = User::factory()->create();

    // Authenticated but not confirmed
    $response = $this->actingAs($user)->get(route('settings.general'));
    $response->assertRedirect(route('password.confirm'));

    // Authenticated and confirmed
    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.general'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('settings/vault/General')
        ->has('settings.site_name')
    );
});

test('general settings can be updated', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('settings.general.update'), [
            'site_name' => 'New Site Name',
            'root_domain' => 'new.example.com',
            'timezone' => 'UTC',
            'puid' => 1000,
            'pgid' => 1000,
        ]);

    $response->assertRedirect();

    // Reload settings
    $settings = resolve(GeneralSettings::class);
    // Refresh strictly from storage
    $settings->refresh();

    expect($settings->site_name)->toBe('New Site Name');
    expect($settings->root_domain)->toBe('new.example.com');
});
