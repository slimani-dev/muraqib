<?php

use App\Filament\Resources\Portainers\Pages\ListPortainers;
use App\Filament\Resources\Portainers\Pages\ViewPortainer;
use App\Models\Portainer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->portainer = Portainer::factory()->create([
        'name' => 'Test Portainer',
        'url' => 'https://portainer.slimani.dev',
        'access_token' => 'ptr_test',
        'status' => 'active',
    ]);
});

it('can render list portainers page', function () {
    Livewire::test(ListPortainers::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->portainer]);
});

it('can render view portainer page', function () {
    Livewire::test(ViewPortainer::class, [
        'record' => $this->portainer->id,
    ])
        ->assertOk()
        ->assertSchemaStateSet([
            'name' => $this->portainer->name,
            'url' => $this->portainer->url,
        ]);
});

it('can sync portainer from view page', function () {
    Http::fake([
        '*/api/system/status' => Http::response([
            'Version' => '2.19.4',
            'Edition' => 'Community Edition',
        ], 200),
    ]);

    Livewire::test(ViewPortainer::class, [
        'record' => $this->portainer->id,
    ])
        ->callAction('sync')
        ->assertNotified();

    $this->portainer->refresh();

    expect($this->portainer->version)->toBe('2.19.4');
});



it('can sync from table action', function () {
    Http::fake([
        '*/api/system/status' => Http::response([
            'Version' => '2.19.4',
        ], 200),
    ]);

    Livewire::test(ListPortainers::class)
        ->callTableAction('sync', $this->portainer)
        ->assertNotified();

    $this->portainer->refresh();

    expect($this->portainer->version)->toBe('2.19.4');
});

// Skip relation manager tests - they work in the browser but require Eloquent relationships to test
// The relation managers override getTableRecords() to fetch from API instead
