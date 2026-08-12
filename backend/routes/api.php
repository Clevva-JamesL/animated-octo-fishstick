<?php

use App\Http\Controllers\Ext\StateController;
use App\Http\Middleware\VerifyTwitchJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifyTwitchJwt::class)->prefix('ext')->group(function () {
    Route::get('/state', StateController::class);
});
