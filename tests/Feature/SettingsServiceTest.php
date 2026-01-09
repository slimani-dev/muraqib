<?php

use App\Services\SettingsService;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

beforeEach(function () {
    GeneralSettings::fake([
        'data' => [],
    ]);
});

it('can set and get a plain value', function () {
    $service = app(SettingsService::class);
    
    $service->set('site_name', 'Muraqib');
    
    expect($service->get('site_name'))->toBe('Muraqib');
});

it('encrypts values with _key suffix', function () {
    $service = app(SettingsService::class);
    $secret = 'super_secret';
    
    $service->set('api_key', $secret);
    
    // Check raw value in settings class
    $settings = app(GeneralSettings::class);
    $settings->refresh();
    
    $storedValue = $settings->data['api_key'];
    expect($storedValue)->not->toBe($secret);
    expect(Crypt::decrypt($storedValue))->toBe($secret);
    
    // Check retrieval via service
    expect($service->get('api_key'))->toBe($secret);
});

it('encrypts values with _token suffix', function () {
    $service = app(SettingsService::class);
    $token = 'access_token_123';
    
    $service->set('access_token', $token);
    
    $settings = app(GeneralSettings::class);
    $settings->refresh();
    
    $storedValue = $settings->data['access_token'];
    expect($storedValue)->not->toBe($token);
    
    expect($service->get('access_token'))->toBe($token);
});

it('returns null if key does not exist', function () {
    $service = app(SettingsService::class);
    expect($service->get('non_existent'))->toBeNull();
});
