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

        Route::get('settings/infrastructure/portainer', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'portainer'])->name('settings.infrastructure.portainer');
        Route::put('settings/infrastructure/portainer', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'updatePortainer'])->name('settings.infrastructure.portainer.update');

        Route::get('settings/infrastructure/cloudflare', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'cloudflare'])->name('settings.infrastructure.cloudflare');
        Route::put('settings/infrastructure/cloudflare', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'updateCloudflare'])->name('settings.infrastructure.cloudflare.update');

        Route::get('settings/infrastructure/proxmox', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'proxmox'])->name('settings.infrastructure.proxmox');
        Route::put('settings/infrastructure/proxmox', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'updateProxmox'])->name('settings.infrastructure.proxmox.update');

        Route::get('settings/media', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'media'])->name('settings.media');
        Route::put('settings/media', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'updateMedia'])->name('settings.media.update');

        Route::get('settings/developer', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'developer'])->name('settings.developer');
        Route::put('settings/developer', [\App\Http\Controllers\Settings\GlobalSettingsController::class, 'updateDeveloper'])->name('settings.developer.update');
    });

    // Test Connection (ajax)
    Route::post('settings/test-connection', [\App\Http\Controllers\Settings\ConnectionTesterController::class, 'test'])->name('settings.test-connection');

    // Cloudflare Tunnel Wizard (ajax)
    Route::prefix('settings/cloudflare')->name('settings.cloudflare.')->group(function () {
        Route::get('status', [\App\Http\Controllers\Api\CloudflareController::class, 'status'])->name('status');
        Route::post('verify', [\App\Http\Controllers\Api\CloudflareController::class, 'verifyToken'])->name('verify');
        Route::post('tunnel', [\App\Http\Controllers\Api\CloudflareController::class, 'createTunnel'])->name('tunnel');
        Route::get('ingress', [\App\Http\Controllers\Api\CloudflareController::class, 'getIngress'])->name('ingress.index');
        Route::post('ingress', [\App\Http\Controllers\Api\CloudflareController::class, 'updateIngress'])->name('ingress.update');
        Route::get('zones', [\App\Http\Controllers\Api\CloudflareController::class, 'listZones'])->name('zones');
        Route::get('records', [\App\Http\Controllers\Api\CloudflareController::class, 'getDnsRecords'])->name('records');
    });

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
