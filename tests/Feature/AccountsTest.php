<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('only admins can open the admin panel', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();
});

test('there is no public sign-up', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', ['name' => 'X', 'email' => 'x@example.com', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertNotFound();

    expect(User::query()->where('email', 'x@example.com')->exists())->toBeFalse();
});

test('admins create users, who are verified and get their own team', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListUsers::class)
        ->callAction('create', data: ['name' => 'Sam', 'email' => 'sam@example.com', 'password' => 'a-long-password-123', 'is_admin' => false])
        ->assertHasNoFormErrors();

    $sam = User::query()->where('email', 'sam@example.com')->first();

    expect($sam)
        ->is_admin->toBeFalse()
        ->email_verified_at->not->toBeNull()
        ->and($sam->personalTeam())->not->toBeNull()
        ->and(Hash::check('a-long-password-123', $sam->password))->toBeTrue();
});

test('the seeder creates the first admin from the environment, once', function () {
    config(['app.admin' => ['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'seeded-password-123']]);

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $admins = User::query()->where('email', 'owner@example.com')->get();

    expect($admins)->toHaveCount(1)
        ->and($admins->first())->is_admin->toBeTrue()->email_verified_at->not->toBeNull()
        ->and(Hash::check('seeded-password-123', $admins->first()->password))->toBeTrue()
        ->and($admins->first()->personalTeam())->not->toBeNull();
});

test('the seeder generates a password when none is configured', function () {
    config(['app.admin' => ['name' => 'Admin', 'email' => 'admin@example.com', 'password' => null]]);

    $this->artisan('db:seed', ['--force' => true])
        ->expectsOutputToContain('Generated password')
        ->assertSuccessful();

    expect(User::query()->where('email', 'admin@example.com')->value('is_admin'))->toBeTrue();
});
