<?php

use App\Http\Controllers\Api\PortainerStacksController;
use App\Http\Controllers\Api\PortainerStatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/test-connection/portainer', [\App\Http\Controllers\Api\ConnectionTesterController::class, 'test']);
Route::group(['prefix' => 'portainer', 'as' => 'portainer.'], function () {
    Route::get('status', [PortainerStatusController::class, 'check'])->name('check');
    Route::get('containers/stats', [PortainerStatusController::class, 'containers'])->name('containers.stats');
    Route::get('stacks/stats', [PortainerStatusController::class, 'stacks'])->name('stacks.stats');

    // Stack Management
    Route::get('stacks', [PortainerStacksController::class, 'index'])->name('stacks.index');
    Route::post('stacks/{id}/start', [PortainerStacksController::class, 'start'])->name('stacks.start');
    Route::post('stacks/{id}/stop', [PortainerStacksController::class, 'stop'])->name('stacks.stop');
    Route::post('stacks/{id}/restart', [PortainerStacksController::class, 'restart'])->name('stacks.restart');
    Route::put('stacks/{id}', [PortainerStacksController::class, 'update'])->name('stacks.update');
});
