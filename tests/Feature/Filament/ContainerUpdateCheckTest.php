<?php

use App\Enums\UpdateStatus;
use App\Filament\Resources\Portainers\Pages\ViewPortainer;
use App\Filament\Resources\Portainers\RelationManagers\ContainersRelationManager;
use App\Models\Container;
use App\Models\Portainer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Cache::clear();
    $this->portainer = Portainer::factory()->create();
    $this->container = Container::factory()->create([
        'portainer_id' => $this->portainer->id,
        'image' => 'nginx:latest',
        'image_digest' => 'sha256:1234abcd',
        'update_status' => UpdateStatus::Unknown,
    ]);
});

it('can check update for a single container and shows up-to-date', function () {
    Http::fake([
        '*/tags*' => Http::response(['tags' => ['latest', 'v1.0.0']], 200),
        '*/digest*' => Http::response(['digest' => 'sha256:1234abcd'], 200),
    ]);

    Livewire::test(ContainersRelationManager::class, [
        'ownerRecord' => $this->portainer,
        'pageClass' => ViewPortainer::class,
    ])
        ->callTableAction('check_update', $this->container)
        ->assertNotified();

    $this->container->refresh();
    expect($this->container->update_status)->toBe(UpdateStatus::UpToDate)
        ->and($this->container->update_checked_at)->not->toBeNull();
});

it('can check update for a single container and shows update available', function () {
    Http::fake([
        '*/tags*' => Http::response(['tags' => ['latest', 'v2.0.0']], 200),
        '*/digest*' => Http::response(['digest' => 'sha256:9999xxxx'], 200),
    ]);

    Livewire::test(ContainersRelationManager::class, [
        'ownerRecord' => $this->portainer,
        'pageClass' => ViewPortainer::class,
    ])
        ->callTableAction('check_update', $this->container)
        ->assertNotified();

    $this->container->refresh();
    expect($this->container->update_status)->toBe(UpdateStatus::UpdateAvailable);
});

it('hides check update action when there is no digest', function () {
    $noDigestContainer = Container::factory()->create([
        'portainer_id' => $this->portainer->id,
        'image_digest' => null,
    ]);

    Livewire::test(ContainersRelationManager::class, [
        'ownerRecord' => $this->portainer,
        'pageClass' => ViewPortainer::class,
    ])
        ->assertTableActionHidden('check_update', $noDigestContainer)
        ->assertTableActionVisible('check_update', $this->container);
});

it('can bulk check updates for multiple containers', function () {
    $container2 = Container::factory()->create([
        'portainer_id' => $this->portainer->id,
        'image' => 'redis:alpine',
        'image_digest' => 'sha256:5678efgh',
        'update_status' => UpdateStatus::Unknown,
    ]);

    Http::fake([
        '*/tags*nginx*' => Http::response(['tags' => ['latest', 'v1.0.0']], 200),
        '*/digest*nginx*' => Http::response(['digest' => 'sha256:1234abcd'], 200), // match
        '*/tags*redis*' => Http::response(['tags' => ['latest', 'v2.0.0']], 200),
        '*/digest*redis*' => Http::response(['digest' => 'sha256:99999999'], 200), // mismatch
    ]);

    Livewire::test(ContainersRelationManager::class, [
        'ownerRecord' => $this->portainer,
        'pageClass' => ViewPortainer::class,
    ])
        ->callTableBulkAction('check_updates', [$this->container, $container2])
        ->assertNotified();

    $this->container->refresh();
    $container2->refresh();

    expect($this->container->update_status)->toBe(UpdateStatus::UpToDate)
        ->and($container2->update_status)->toBe(UpdateStatus::UpdateAvailable);
});

it('gracefully handles missing tags in API response', function () {
    Http::fake([
        '*/tags*' => Http::response(['error' => 'Not found'], 404),
    ]);

    Livewire::test(ContainersRelationManager::class, [
        'ownerRecord' => $this->portainer,
        'pageClass' => ViewPortainer::class,
    ])
        ->callTableAction('check_update', $this->container)
        ->assertNotified();

    $this->container->refresh();
    expect($this->container->update_status)->toBe(UpdateStatus::Error);
});
