<?php

use Illuminate\Support\Facades\Route;
use Modules\ApluAnalytics\Http\Controllers\ApluAnalyticsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('apluanalytics', ApluAnalyticsController::class)->names('apluanalytics');
});
