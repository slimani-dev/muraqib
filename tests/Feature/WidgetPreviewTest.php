<?php

use App\Actions\Teams\CreateTeam;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot open the widget preview', function () {
    $user = User::factory()->create();
    $team = app(CreateTeam::class)->handle($user, 'Preview Team', isPersonal: true);

    $this->get(route('dashboard.widgets', ['current_team' => $team->slug]))
        ->assertRedirect(route('login'));
});

test('team members can open the widget preview with container data', function () {
    $user = User::factory()->create();
    $team = app(CreateTeam::class)->handle($user, 'Preview Team', isPersonal: true);
    $user->update(['current_team_id' => $team->id]);

    $this->actingAs($user)
        ->get(route('dashboard.widgets', ['current_team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/WidgetPreview')
            ->has('containers')
            ->has('netdata')
            ->has('agenda_cached'));
});
