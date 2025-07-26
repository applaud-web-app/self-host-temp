<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomPrompt\Http\Controllers\CustomPromptController;

Route::prefix('customprompts')->middleware(['auth', 'verified'])->name('customprompt.')->controller(CustomPromptController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('create/{domain}', 'create')->name('create');
    Route::get('update/{domain}', 'update')->name('update'); // This can be a GET route to show the form
    Route::put('update/{domain}', 'store')->name('update'); // Use PUT for update
    Route::post('store/{domain}', 'store')->name('store');
    Route::post('update-status/{id}', 'updateStatus')->name('updateStatus');
});
