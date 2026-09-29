<?php

use App\Models\User;

test('the home page sends guests to the login page', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

test('the home page sends signed-in users to their dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertRedirect(route('dashboard.home'));
});
