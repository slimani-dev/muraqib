<?php

use App\Enums\MediaServiceStatus;
use App\Enums\MediaServiceType;
use App\Models\MediaService;
use App\Rules\PublicUrl;
use App\Services\Media\BazarrClient;
use App\Services\Media\MediaStatusChecker;
use App\Services\Media\RadarrClient;
use App\Services\Media\TransmissionClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

function mediaService(array $attributes = []): MediaService
{
    return new MediaService([
        'type' => MediaServiceType::Radarr,
        'name' => 'Radarr',
        'url' => 'https://radarr.example.com/',
        'api_key' => 'radarr-test-key',
        ...$attributes,
    ]);
}

it('calls the stored url with the stored api key', function () {
    Http::fake(['radarr.example.com/*' => Http::response([])]);

    (new RadarrClient(mediaService()))->getData(fresh: true);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://radarr.example.com/api/v3/movie'
        && $request->hasHeader('X-Api-Key', 'radarr-test-key'));
});

it('checks the api connection', function (int $status, bool $connected) {
    Http::fake(['radarr.example.com/api/v3/system/status' => Http::response([], $status)]);

    expect(mediaService()->client()->checkConnection())->toBe($connected);
})->with([
    'accepted' => [200, true],
    'wrong key' => [401, false],
]);

it('accepts the transmission session handshake as a working connection', function () {
    Http::fake(['transmission.example.com/*' => Http::response('', 409, ['X-Transmission-Session-Id' => 'abc'])]);

    $service = mediaService([
        'type' => MediaServiceType::Transmission,
        'url' => 'https://transmission.example.com',
        'username' => 'admin',
        'password' => 'secret',
        'settings' => ['rpc_path' => '/transmission/rpc'],
    ]);

    expect((new TransmissionClient($service))->checkConnection())->toBeTrue();

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://transmission.example.com/transmission/rpc'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('admin:secret')));
});

it('maps the server status check response', function (int $status, MediaServiceStatus $expected) {
    Http::fake(['status.example.com/*' => Http::response('', $status)]);

    $service = mediaService(['status_url' => 'https://status.example.com/health', 'status_headers' => ['X-Gate-Token' => 'letmein']]);

    expect(app(MediaStatusChecker::class)->check($service))->toBe($expected);

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('X-Gate-Token', 'letmein'));
})->with([
    'ok' => [200, MediaServiceStatus::Up],
    'redirect' => [302, MediaServiceStatus::Up],
    'token rejected' => [403, MediaServiceStatus::Blocked],
    'server error' => [502, MediaServiceStatus::Down],
]);

it('reports down when the status url cannot be reached', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    expect(app(MediaStatusChecker::class)->check(mediaService()))->toBe(MediaServiceStatus::Down);
});

it('picks where the status check runs', function (array $attributes, ?string $mode) {
    Http::fake();

    expect(app(MediaStatusChecker::class)->forDashboard(mediaService($attributes))['mode'] ?? null)->toBe($mode);
})->with([
    'public https url, no headers' => [[], 'browser'],
    'headers set' => [['status_headers' => ['X-Gate-Token' => 'letmein']], 'server'],
    'plain http url' => [['url' => 'http://radarr.example.com'], 'server'],
    'lan url and no status url' => [['url' => 'http://192.168.1.10:7878'], null],
    'lan url with a public status url' => [['url' => 'http://192.168.1.10:7878', 'status_url' => 'https://radarr.example.com'], 'browser'],
]);

it('never exposes the status headers to the dashboard', function () {
    Http::fake();

    $status = app(MediaStatusChecker::class)->forDashboard(mediaService(['status_headers' => ['X-Gate-Token' => 'letmein']]));

    expect(json_encode($status))->not->toContain('letmein');
});

it('only accepts public urls for status checks', function (string $url, bool $isPublic) {
    expect(PublicUrl::isPublic($url))->toBe($isPublic);
})->with([
    ['https://jellyfin.example.com', true],
    ['https://1.1.1.1', true],
    ['http://192.168.1.5:8096', false],
    ['http://10.0.0.2', false],
    ['http://127.0.0.1', false],
    ['http://localhost:8080', false],
    ['https://[fd00::1]', false],
    ['http://nas.local', false],
    ['https://radarr.home.arpa', false],
    ['http://jellyfin', false],
]);

it('never caches a failed fetch', function () {
    Http::fake(['radarr.example.com/*' => Http::response('', 500)]);

    expect((new RadarrClient(mediaService()))->getData(fresh: true))->toBeNull()
        ->and(Cache::has(mediaService()->cacheKey('data')))->toBeFalse();
});

it('keeps the last good data when a refresh fails', function () {
    Cache::flexible(mediaService()->cacheKey('data'), [10, 86400], fn () => ['name' => 'Radarr', 'good' => true]);
    $this->travel(20)->seconds();
    Http::fake(['radarr.example.com/*' => Http::response('', 503)]);

    expect((new RadarrClient(mediaService()))->getData())->toMatchArray(['good' => true]);

    $this->travelBack();
    expect(Cache::get(mediaService()->cacheKey('data')))->toMatchArray(['good' => true]);
});

it('counts bazarr episode subtitles from the series list', function () {
    Http::fake([
        'bazarr.example.com/api/series' => Http::response(['data' => [
            ['episodeFileCount' => 10, 'episodeMissingCount' => 2],
            ['episodeFileCount' => 5, 'episodeMissingCount' => 1],
        ]]),
        'bazarr.example.com/*' => Http::response(['data' => []]),
    ]);

    $service = mediaService(['type' => MediaServiceType::Bazarr, 'url' => 'https://bazarr.example.com']);

    expect((new BazarrClient($service))->getData(fresh: true)['stats'][1]['value'])->toBe('3 / 15');
});
