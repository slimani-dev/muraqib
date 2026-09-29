<?php

use App\Actions\Teams\CreateTeam;
use App\Enums\MediaServiceType;
use App\Filament\Resources\MediaServices\Pages\ListMediaServices;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\MediaService;
use App\Models\User;
use App\Services\MediaArrService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

function dashboardUser(): array
{
    $user = User::factory()->admin()->create();
    $team = app(CreateTeam::class)->handle($user, 'Media Team', isPersonal: true);
    $user->update(['current_team_id' => $team->id]);

    return [$user, $team];
}

test('secrets are encrypted at rest', function () {
    $service = MediaService::factory()->transmission()->create([
        'password' => 'plain-password',
        'status_headers' => ['X-Gate-Token' => 'plain-token'],
    ]);

    $row = DB::table('media_services')->find($service->id);

    expect($row->password)->not->toContain('plain-password')
        ->and($row->status_headers)->not->toContain('plain-token')
        ->and($service->fresh()->password)->toBe('plain-password');
});

test('disabled services are left out', function () {
    MediaService::factory()->sonarr()->disabled()->create();
    Http::fake();

    $media = new MediaArrService;

    expect($media->services())->toBeEmpty()
        ->and($media->getAgendaData())->toBe([]);

    Http::assertNothingSent();
});

test('the agenda works with only radarr configured', function () {
    MediaService::factory()->radarr()->create();
    Http::fake(['radarr.example.com/api/v3/calendar*' => Http::response([
        ['id' => 1, 'title' => 'Upcoming Movie', 'hasFile' => false, 'digitalRelease' => now()->addDays(3)->toIso8601String()],
    ])]);

    expect((new MediaArrService)->getAgendaData())
        ->toHaveCount(1)
        ->{0}->title->toBe('Upcoming Movie');
});

test('the media api returns 404 for a disabled service', function () {
    [$user] = dashboardUser();
    $radarr = MediaService::factory()->radarr()->disabled()->create();

    $this->actingAs($user)->getJson(route('api.media.show', $radarr))->assertNotFound();
});

test('the agenda merges every radarr and sonarr', function () {
    $first = MediaService::factory()->radarr()->create(['url' => 'https://movies-a.example.com']);
    $second = MediaService::factory()->radarr()->create(['url' => 'https://movies-b.example.com']);
    Http::fake([
        'movies-a.example.com/*' => Http::response([['id' => 1, 'title' => 'Movie A', 'hasFile' => false, 'digitalRelease' => now()->addDay()->toIso8601String()]]),
        'movies-b.example.com/*' => Http::response([['id' => 1, 'title' => 'Movie B', 'hasFile' => false, 'digitalRelease' => now()->addDays(2)->toIso8601String()]]),
    ]);

    expect(collect((new MediaArrService)->getAgendaData())->pluck('id')->all())
        ->toBe(["radarr_{$first->id}_1", "radarr_{$second->id}_1"]);
});

test('the dashboard lists every enabled service, several per type, without their secrets', function () {
    [$user, $team] = dashboardUser();
    $first = MediaService::factory()->jellyfin()->create(['name' => 'Home', 'api_key' => 'super-secret-key']);
    $second = MediaService::factory()->jellyfin()->create(['name' => 'Family', 'url' => 'https://family.example.com']);
    MediaService::factory()->radarr()->disabled()->create();
    Http::fake();

    $response = $this->actingAs($user)->get(route('dashboard', ['current_team' => $team->slug]));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('media_services', 2)
        ->where('media_services.0.id', $first->id)
        ->where('media_services.0.type', 'jellyfin')
        ->where('media_services.1.name', 'Family')
        ->where('media_services.1.status.mode', 'browser')
        ->where('media_services.1.status.url', 'https://family.example.com')
        ->has("media_{$second->id}_cached"));

    expect($response->getContent())->not->toContain('super-secret-key');
});

test('polls reuse cached data and the refresh button bypasses the cache once per window', function () {
    [$user, $team] = dashboardUser();
    $radarr = MediaService::factory()->radarr()->create();
    $key = $radarr->cacheKey('data');
    Cache::flexible($key, [10, 86400], fn () => ['name' => 'Radarr', 'cached' => true]);
    Http::fake(['radarr.example.com/*' => Http::response([])]);

    $reload = fn (array $headers = []) => $this->actingAs($user)->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
        'X-Inertia-Partial-Component' => 'Dashboard',
        'X-Inertia-Partial-Data' => "media_{$radarr->id}",
        ...$headers,
    ])->get(route('dashboard', ['current_team' => $team->slug]));

    $prop = "props.media_{$radarr->id}.cached";

    expect($reload()->json($prop))->toBeTrue();
    Http::assertNothingSent();

    expect($reload(['X-Media-Refresh' => (string) $radarr->id])->json($prop))->toBeNull();
    Http::assertSentCount(4);

    Cache::forget($key);
    Cache::flexible($key, [10, 86400], fn () => ['name' => 'Radarr', 'cached' => true]);
    expect($reload(['X-Media-Refresh' => (string) $radarr->id])->json($prop))->toBeTrue();
});

test('a service is created with the new action, and several of one type are allowed', function () {
    MediaService::factory()->jellyfin()->create(['name' => 'Home']);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListMediaServices::class)
        ->callAction('create', data: [
            'type' => 'jellyfin',
            'name' => 'Family',
            'url' => 'https://family.example.com',
            'api_key' => 'family-key',
            'is_enabled' => true,
            'status_headers' => ['X-Gate-Token' => 'letmein'],
        ])
        ->assertHasNoFormErrors();

    expect(MediaService::query()->where('type', MediaServiceType::Jellyfin)->pluck('name')->all())->toBe(['Home', 'Family'])
        ->and(MediaService::query()->where('name', 'Family')->first())
        ->api_key->toBe('family-key')
        ->status_headers->toBe(['X-Gate-Token' => 'letmein']);
});

test('transmission asks for a username and password instead of an api key', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListMediaServices::class)
        ->callAction('create', data: [
            'type' => 'transmission',
            'name' => 'Torrents',
            'url' => 'https://torrents.example.com',
            'username' => 'admin',
            'password' => 'secret',
            'settings' => ['rpc_path' => '/transmission/rpc'],
        ])
        ->assertHasNoFormErrors();

    expect(MediaService::query()->first())
        ->api_key->toBeNull()
        ->password->toBe('secret');
});

test('a private status url is rejected', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListMediaServices::class)
        ->callAction('create', data: [
            'type' => 'radarr',
            'name' => 'Radarr',
            'url' => 'http://192.168.1.10:7878',
            'api_key' => 'key',
            'status_url' => 'http://192.168.1.10:7878',
        ])
        ->assertHasFormErrors(['status_url']);
});

test('editing keeps the stored secret when the field is left blank and never shows it', function () {
    $radarr = MediaService::factory()->radarr()->create(['api_key' => 'stored-key']);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListMediaServices::class)
        ->mountTableAction('edit', $radarr)
        ->assertTableActionDataSet(['api_key' => null])
        ->setTableActionData(['name' => 'Movies', 'api_key' => ''])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    expect($radarr->fresh())
        ->name->toBe('Movies')
        ->api_key->toBe('stored-key');
});

test('media:import-env imports the old env configuration and is idempotent', function () {
    $env = [
        'RADARR_URL' => 'https://radarr.example.com/',
        'RADARR_API_KEY' => 'env-key',
        'TRANSMISSION_URL' => 'https://transmission.example.com',
        'TRANSMISSION_USERNAME' => 'admin',
        'TRANSMISSION_PASSWORD' => 'env-password',
    ];

    foreach (['JELLYFIN', 'SEERR', 'SONARR', 'BAZARR'] as $prefix) {
        $env["{$prefix}_URL"] = '';
    }

    foreach ($env as $key => $value) {
        $_ENV[$key] = $_SERVER[$key] = $value;
    }

    $this->artisan('media:import-env')->assertSuccessful();
    $this->artisan('media:import-env')->assertSuccessful();

    expect(MediaService::query()->count())->toBe(2)
        ->and(MediaService::forType(MediaServiceType::Radarr))
        ->url->toBe('https://radarr.example.com')
        ->api_key->toBe('env-key')
        ->and(MediaService::forType(MediaServiceType::Transmission)->password)->toBe('env-password');
});

test('a single app can be edited from the table without re-entering its secret', function () {
    $transmission = MediaService::factory()->transmission()->create(['password' => 'stored-password']);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListMediaServices::class)
        ->assertCanSeeTableRecords([$transmission])
        ->callTableAction('edit', $transmission, data: ['url' => 'https://torrents.example.com', 'password' => ''])
        ->assertHasNoTableActionErrors();

    expect($transmission->fresh())
        ->url->toBe('https://torrents.example.com')
        ->password->toBe('stored-password')
        ->settings->toBe(['rpc_path' => '/transmission/rpc']);
});
