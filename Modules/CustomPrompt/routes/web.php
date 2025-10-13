<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomPrompt\Http\Controllers\CustomPromptController;

Route::prefix('custom-prompt')->middleware(['auth'])->name('customprompt.')->controller(CustomPromptController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('create/{domain}', 'create')->name('create');
    Route::get('update/{domain}', 'update')->name('update'); 
    Route::post('update/{domain}', 'store')->name('update');
    Route::post('store/{domain}', 'store')->name('store');
    Route::post('update-status/{id}', 'updateStatus')->name('updateStatus');
    Route::get('integrate', 'integrate')->name('integrate');
    Route::get('download-plugin/{domain}', 'downloadPlugin')->name('download-plugin');
    Route::get('verify-integration', 'verifyIntegration')->name('verify-integration');
});
