<?php

use App\Actions\Teams\CreateTeam;
use App\Http\Controllers\Api\ContainerApiController;
use App\Http\Controllers\Api\MediaApiController;
use App\Http\Controllers\Api\NetdataApiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('dashboard/weather/refresh', [DashboardController::class, 'refreshWeather'])->name('weather.refresh');
    });

Route::get('dashboard', function () {
    $user = auth()->user();
    if (! $user->currentTeam) {
        $team = app(CreateTeam::class)->handle($user, $user->name."'s Team", isPersonal: true);
        $user->update(['current_team_id' => $team->id]);
        $user->refresh();
    }

    return redirect()->route('dashboard', ['current_team' => $user->currentTeam->slug]);
})->middleware(['auth', 'verified']);

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');

    Route::group(['prefix' => 'api/media', 'as' => 'api.media.'], function () {
        Route::get('jellyfin', [MediaApiController::class, 'jellyfin'])->name('jellyfin');
        Route::get('seerr', [MediaApiController::class, 'seerr'])->name('seerr');
        Route::get('radarr', [MediaApiController::class, 'radarr'])->name('radarr');
        Route::get('sonarr', [MediaApiController::class, 'sonarr'])->name('sonarr');
        Route::get('bazarr', [MediaApiController::class, 'bazarr'])->name('bazarr');
        Route::get('transmission', [MediaApiController::class, 'transmission'])->name('transmission');
    });

    Route::get('api/netdata', [NetdataApiController::class, 'index'])->name('api.netdata.index');
    Route::get('api/containers/ping', [ContainerApiController::class, 'ping'])->name('api.containers.ping');
    Route::post('api/containers/{container}/check-update', [ContainerApiController::class, 'checkUpdate'])->name('api.containers.check-update');
});

require __DIR__.'/settings.php';
