<?php

use App\Filament\Clusters\Settings\Pages\Weather;
use App\Http\Controllers\DashboardController;
use App\Models\User;
use App\Settings\WeatherSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('only admins can open the weather settings', function () {
    $this->actingAs(User::factory()->create())->get(Weather::getUrl())->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get(Weather::getUrl())->assertOk();
});

test('the weather location and unsplash key are saved, the key encrypted and kept when left blank', function () {
    $page = Livewire::actingAs(User::factory()->admin()->create())
        ->test(Weather::class)
        ->fillForm(['latitude' => 36.75, 'longitude' => 3.06, 'location_name' => 'Algiers', 'unsplash_key' => 'unsplash-secret'])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(WeatherSettings::class)->refresh();

    expect($settings)->latitude->toBe(36.75)->location_name->toBe('Algiers')->unsplash_key->toBe('unsplash-secret')
        ->and(DB::table('settings')->where('group', 'weather')->where('name', 'unsplash_key')->value('payload'))->not->toContain('unsplash-secret');

    $page->fillForm(['latitude' => 36.75, 'longitude' => 3.06, 'location_name' => 'Alger', 'unsplash_key' => ''])->call('save');

    expect(app(WeatherSettings::class)->refresh())->location_name->toBe('Alger')->unsplash_key->toBe('unsplash-secret');
});

test('the dashboard uses the weather settings and skips unsplash without a key', function () {
    $settings = app(WeatherSettings::class);
    $settings->fill(['latitude' => 36.75, 'longitude' => 3.06, 'location_name' => 'Algiers', 'unsplash_key' => null])->save();
    Http::fake(['api.open-meteo.com/*' => Http::response(['current' => ['is_day' => 1, 'weather_code' => 0]])]);

    (new ReflectionMethod(DashboardController::class, 'getWeatherData'))->invoke(app(DashboardController::class));

    Http::assertSent(fn ($request) => str_contains($request->url(), 'latitude=36.75'));
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'unsplash'));
});
