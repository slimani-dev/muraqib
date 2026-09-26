<?php

use App\Services\RegistryApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Cache::clear();
});

it('strips tags from image names correctly', function () {
    $service = new class extends RegistryApiService
    {
        public function testStripTag($image)
        {
            return $this->stripTag($image);
        }
    };

    expect($service->testStripTag('nginx:latest'))->toBe('nginx')
        ->and($service->testStripTag('ghcr.io/goauthentik/server:2026.1.1'))->toBe('ghcr.io/goauthentik/server')
        ->and($service->testStripTag('lscr.io/linuxserver/radarr:latest'))->toBe('lscr.io/linuxserver/radarr')
        ->and($service->testStripTag('redis'))->toBe('redis');
});

it('can fetch tags', function () {
    Http::fake([
        '*/tags*' => Http::response([
            'tags' => ['latest', 'v1.0.0', 'v2.0.0'],
        ], 200),
    ]);

    $service = new RegistryApiService;
    $result = $service->getTags('nginx');

    expect($result)->toBeArray()
        ->and($result)->toContain('v2.0.0');
});

it('can determine the latest stable tag using semver', function () {
    $service = new RegistryApiService;

    $tags = ['latest', 'v1.0.0', '1.5.0', 'v2.0.0', 'sha256-abcde', 'main'];
    expect($service->getLatestStableTag($tags))->toBe('v2.0.0');

    $tagsNoV = ['1.0.0', '2.0.0', '1.5.0'];
    expect($service->getLatestStableTag($tagsNoV))->toBe('2.0.0');

    $tagsOnlyLatest = ['latest', 'sha256-abc'];
    expect($service->getLatestStableTag($tagsOnlyLatest))->toBe('latest');
});

it('can check digest by fetching tags then digest', function () {
    Http::fake([
        '*/tags*' => Http::response([
            'tags' => ['latest', 'v2.0.0'],
        ], 200),
        '*/digest*' => Http::response([
            'digest' => 'sha256:abcd',
        ], 200),
    ]);

    $service = new RegistryApiService;
    $result = $service->checkDigest('nginx:latest', 'sha256:1234');

    expect($result)->toBeArray()
        ->and($result['is_latest'])->toBeFalse()
        ->and($result['latest_tag'])->toBe('v2.0.0')
        ->and($result['latest_digest'])->toBe('sha256:abcd');

    // Check match
    $resultMatch = $service->checkDigest('nginx:latest', 'sha256:abcd');
    expect($resultMatch['is_latest'])->toBeTrue();
});

it('gracefully handles missing tags', function () {
    Http::fake([
        '*/tags*' => Http::response(['error' => 'Not found'], 404),
    ]);

    $service = new RegistryApiService;
    $result = $service->checkDigest('nginx:latest', 'sha256:1234');

    expect($result)->toBeArray()
        ->and($result['error'])->toContain('Failed to fetch image tags');
});
