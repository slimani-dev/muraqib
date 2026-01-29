<?php

use App\Filament\Resources\Cloudflares\Pages\ListCloudflares;
use App\Models\Cloudflare;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    // Clear cache if used
});

it('can create cloudflare when connection is verified', function () {
    $livewire = Livewire::actingAs($this->user)
        ->test(ListCloudflares::class);

    $livewire->mountAction('create');

    $livewire->fillForm([
        'name' => 'Prod Cloudflare',
        'account_id' => '12345',
        'api_token' => 'valid-token',
        'connection_status' => 'success',
    ]);

    // Mock HTTP for any service calls that might happen during create (none in ListCloudflares except purely DB create,
    // BUT the CreateAction -> action() closure in ListCloudflares::getHeaderActions was the Wizard action.
    // The standard CreateAction::make() uses default creation unless customized.
    // Wait, ListCloudflares has a custom 'setup_wizard' action but the 'create' action is standard
    // except for my before() hook.
    // Standard CreateAction creates the record.

    $livewire->callMountedAction();

    expect(Cloudflare::count())->toBe(1);
    $cloudflare = Cloudflare::first();
    expect($cloudflare->name)->toBe('Prod Cloudflare');
});

it('cannot create cloudflare when connection is failed', function () {
    $livewire = Livewire::actingAs($this->user)
        ->test(ListCloudflares::class);

    $livewire->mountAction('create');

    $livewire->fillForm([
        'name' => 'Bad Cloudflare',
        'account_id' => '12345',
        'api_token' => 'invalid-token',
        // Default connection_status is pending/null, or we simulate error
        'connection_status' => 'error',
    ]);

    $livewire->callMountedAction(); // Should halt

    expect(Cloudflare::count())->toBe(0);

    // Check that we got the validation error (if we want to be specific)
    // $livewire->assertHasFormErrors(['connection_status']); // Not a real validation error, it's a halt.
});
