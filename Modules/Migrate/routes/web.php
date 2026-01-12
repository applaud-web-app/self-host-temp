<?php

use Illuminate\Support\Facades\Route;
use Modules\Migrate\Http\Controllers\MigrateController;

// ,'verify_migrate'
Route::prefix('mig')->middleware(['auth'])->name('mig.')->controller(MigrateController::class)->group(function () {
    Route::get('/import', 'import')->name('import');
    Route::get('/', 'index')->name('index');
    Route::post('/upload', 'upload')->name('upload');
    Route::get('/report', 'report')->name('report');
    Route::get('/send-notification', 'sendNotification')->name('send-notification');
    Route::post('/store', 'store')->name('store');
    Route::get('/task-tracker', 'taskTracker')->name('task-tracker');
    Route::get('/empty-tracker', 'emptyTracker')->name('empty-tracker');
    Route::get('/overview', 'overview')->name('overview');
    Route::get('/fetch-migrate-data', 'fetchMigrateData')->name('fetch-migrate-data');
    Route::post('/validate-migrate-subs', 'validateMigrateSubs')->name('validate-migrate-subs');
});