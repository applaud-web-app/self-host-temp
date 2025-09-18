<?php

use Illuminate\Support\Facades\Route;
use Modules\Migrate\Http\Controllers\MigrateController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('migrates', MigrateController::class)->names('migrate');
});
