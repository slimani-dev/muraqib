<?php

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->localHotFile = storage_path('framework/testing/vite.hot');
    $this->tunnelHotFile = storage_path('framework/vite-tunnel.hot');

    File::ensureDirectoryExists(dirname($this->localHotFile));
    File::put($this->localHotFile, 'http://127.0.0.1:5173/vite');
    File::delete($this->tunnelHotFile);

    app(Vite::class)->useHotFile($this->localHotFile);
    config(['app.vite_dev_tunnel_url' => 'https://tunnel.example.com']);

    Route::middleware('web')->get('/_vite-tunnel-check', fn () => app(Vite::class)->hotFile());
});

afterEach(function () {
    File::delete([$this->localHotFile, $this->tunnelHotFile]);
});

test('requests through the tunnel load dev assets from its /vite path', function () {
    $this->get('https://tunnel.example.com/_vite-tunnel-check')
        ->assertOk()
        ->assertSee($this->tunnelHotFile, escape: false);

    expect(File::get($this->tunnelHotFile))->toBe('https://tunnel.example.com/vite');
});

test('requests on the local .test host keep using the local dev server', function () {
    $this->get('https://muraqib.test/_vite-tunnel-check')
        ->assertOk()
        ->assertSee($this->localHotFile, escape: false);

    expect(File::exists($this->tunnelHotFile))->toBeFalse();
});

test('nothing changes when vite dev is not running', function () {
    File::delete($this->localHotFile);

    $this->get('https://tunnel.example.com/_vite-tunnel-check')
        ->assertOk()
        ->assertSee($this->localHotFile, escape: false);
});
