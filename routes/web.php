<?php

use App\Actions\Teams\CreateTeam;
use App\Http\Controllers\Api\ContainerApiController;
use App\Http\Controllers\Api\GitHubNotificationController;
use App\Http\Controllers\Api\MediaApiController;
use App\Http\Controllers\Api\NetdataApiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardPageController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\WidgetPreviewController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

// No public landing page: signed-in users go to their dashboard, everyone else to the login page
Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard.home' : 'login'))->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('dashboard/weather/refresh', [DashboardController::class, 'refreshWeather'])->name('weather.refresh');

        if (! app()->isProduction()) {
            Route::get('dashboard/widgets', WidgetPreviewController::class)->name('dashboard.widgets');
        }

        // Dashboard pages; each stores its own layout. Page slugs never use DashboardPageController::RESERVED_SLUGS.
        Route::get('dashboard/{page}', [DashboardController::class, 'index'])
            ->where('page', '[a-z0-9-]+')
            ->name('dashboard.page');
        Route::post('dashboard-pages', [DashboardPageController::class, 'store'])->name('dashboard.pages.store');
        Route::patch('dashboard-pages/{dashboardPage}', [DashboardPageController::class, 'update'])->name('dashboard.pages.update');
        Route::delete('dashboard-pages/{dashboardPage}', [DashboardPageController::class, 'destroy'])->name('dashboard.pages.destroy');
    });

Route::get('dashboard', function () {
    $user = auth()->user();
    if (! $user->currentTeam) {
        $team = app(CreateTeam::class)->handle($user, $user->name."'s Team", isPersonal: true);
        $user->update(['current_team_id' => $team->id]);
        $user->refresh();
    }

    return redirect()->route('dashboard', ['current_team' => $user->currentTeam->slug]);
})->middleware(['auth', 'verified'])->name('dashboard.home');

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');

    Route::get('api/media/{mediaService}', [MediaApiController::class, 'show'])->name('api.media.show');
    Route::post('api/github/{gitAccount}/notifications', [GitHubNotificationController::class, 'update'])->name('api.github.notifications.update');

    Route::get('api/netdata', [NetdataApiController::class, 'index'])->name('api.netdata.index');
    Route::get('api/containers/ping', [ContainerApiController::class, 'ping'])->name('api.containers.ping');
    Route::post('api/containers/{container}/check-update', [ContainerApiController::class, 'checkUpdate'])->name('api.containers.check-update');
});

require __DIR__.'/settings.php';
