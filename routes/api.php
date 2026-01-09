<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PortainerStatusController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/test-connection/portainer', [\App\Http\Controllers\Api\ConnectionTesterController::class, 'test']);
Route::group(['prefix' => 'portainer', 'as' => 'portainer.'], function () {
    Route::get('status', [PortainerStatusController::class, 'check'])->name('check');
    Route::get('containers', [PortainerStatusController::class, 'containers'])->name('containers');
    Route::get('stacks', [PortainerStatusController::class, 'stacks'])->name('stacks');
});
