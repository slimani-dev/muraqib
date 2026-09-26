<?php

use App\Services\MediaArrService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('reads radarr connection details from config', function () {
    config([
        'services.media.radarr.url' => 'https://radarr.example.com/',
        'services.media.radarr.key' => 'radarr-test-key',
    ]);

    Http::fake(['radarr.example.com/*' => Http::response([])]);

    (new MediaArrService)->getRadarrData(fresh: true);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://radarr.example.com/api/v3/movie'
        && $request->hasHeader('X-Api-Key', 'radarr-test-key'));
});

it('returns data without throwing when the media stack is not configured', function () {
    config(['services.media.radarr.url' => null, 'services.media.radarr.key' => null]);

    Http::fake();

    expect((new MediaArrService)->getRadarrData(fresh: true))->toBeArray();
});
