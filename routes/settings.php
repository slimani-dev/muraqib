<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Global Settings & Vault (Protected by Password Confirmation)
    Route::middleware(['password.confirm'])->group(function () {
        Route::get('settings/general', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'general'])->name('settings.general');
        Route::put('settings/general', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'updateGeneral'])->name('settings.general.update');

        Route::get('settings/infrastructure', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'infrastructure'])->name('settings.infrastructure');
        Route::put('settings/infrastructure', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'updateInfrastructure'])->name('settings.infrastructure.update');

        Route::get('settings/media', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'media'])->name('settings.media');
        Route::put('settings/media', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'updateMedia'])->name('settings.media.update');

        Route::get('settings/developer', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'developer'])->name('settings.developer');
        Route::put('settings/developer', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'updateDeveloper'])->name('settings.developer.update');
    });

    // Test Connection (ajax)
    Route::post('settings/test-connection', [\App\Http\Controllers\Settings\ConnectionTesterController::class, 'test'])->name('settings.test-connection');

    // Redirect /settings to /settings/general
    Route::get('settings', function () {
        return redirect()->route('settings.general');
    })->name('settings.redirect');

    // Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});
