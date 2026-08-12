<?php

use App\Http\Controllers\Ext\DeathController;
use App\Http\Controllers\Ext\SessionController;
use App\Http\Controllers\Ext\StateController;
use App\Http\Middleware\VerifyTwitchJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifyTwitchJwt::class)->prefix('ext')->group(function () {
    Route::get('/state', StateController::class);

    Route::post('/sessions', [SessionController::class, 'store']);
    Route::patch('/sessions/current', [SessionController::class, 'updateCurrent']);
    Route::post('/sessions/current/end', [SessionController::class, 'endCurrent']);

    Route::post('/deaths', [DeathController::class, 'store']);
    Route::patch('/deaths/{death}', [DeathController::class, 'update']);
});
