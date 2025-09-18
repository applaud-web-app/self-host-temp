<?php

use Illuminate\Support\Facades\Route;
use Modules\AdvanceAnalytics\Http\Controllers\AdvanceAnalyticsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('advanceanalytics', AdvanceAnalyticsController::class)->names('advanceanalytics');
});
