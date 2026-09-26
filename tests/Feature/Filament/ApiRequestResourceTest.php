<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ApiRequests\ApiRequestResource;
use App\Filament\Resources\ApiRequests\Pages\ListApiRequests;
use App\Filament\Resources\ApiRequests\Pages\ViewApiRequest;
use App\Models\ApiRequest;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['email_verified_at' => now()]));
});

it('can render list page', function () {
    $this->get(ApiRequestResource::getUrl('index'))->assertSuccessful();
});

it('can list api requests', function () {
    $requests = ApiRequest::factory()->count(5)->create();

    Livewire::test(ListApiRequests::class)
        ->assertCanSeeTableRecords($requests);
});

it('can view api request details', function () {
    $request = ApiRequest::factory()->create([
        'service' => 'Cloudflare',
        'url' => 'https://api.cloudflare.com/client/v4',
        'request_headers' => ['Authorization' => ['Bearer token']],
    ]);

    $this->get(ApiRequestResource::getUrl('view', ['record' => $request]))
        ->assertSuccessful();

    Livewire::test(ViewApiRequest::class, ['record' => $request->getKey()])
        ->assertSee('Cloudflare');
});

it('can filter deleted records', function () {
    $request = ApiRequest::factory()->create();
    $deletedRequest = ApiRequest::factory()->create(['deleted_at' => now()]);

    Livewire::test(ListApiRequests::class)
        ->assertCanSeeTableRecords([$request])
        ->assertCanNotSeeTableRecords([$deletedRequest])
        ->filterTable('trashed', true)
        ->assertCanSeeTableRecords([$request]);
});
