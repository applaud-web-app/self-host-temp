<?php

use Illuminate\Support\Facades\Route;
use Modules\URLShortener\Http\Controllers\URLShortenerController;

Route::prefix('shortener')->middleware(['auth','verify_url_shortener'])->name('url_shortener.')->controller(URLShortenerController::class)->group(function () {

    // YOUTUBE SHORTENER
    Route::get('/youtube', 'youtube')->name('youtube');
    Route::get('/create-youtube', 'createYoutube')->name('youtube.create');
    Route::post('/youtube-store', 'youtubeStore')->name('youtube.store');
    Route::get('/youtube-list', 'youtubeList')->name('youtube.list');
    Route::post('youtube-status', 'youtubeStatus')->name('youtube.status');
    Route::get('/delete-youtube/{id}', 'deleteYoutube')->name('youtube.delete');
    
    // URL SHORTENER
    Route::get('/link', 'link')->name('link');
    Route::get('/create-link', 'createLink')->name('link.create');
    Route::post('/link-store', 'linkStore')->name('link.store');
    Route::get('/link-list', 'linkList')->name('link.list');
    Route::post('link-status', 'linkStatus')->name('link.status');
    Route::get('/delete-link/{id}', 'deleteLink')->name('link.delete');

    // COMMON ROUTES
    Route::get('/index', 'index')->name('index');
    // Route::get('/report', 'report')->name('report');
    // Route::post('store', 'store')->name('store');
});