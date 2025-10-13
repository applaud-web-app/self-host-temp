<?php

use Illuminate\Support\Facades\Route;
use Modules\URLShortener\Http\Controllers\URLShortenerController;

Route::prefix('shortener')->middleware(['auth'])->name('url_shortener.')->controller(URLShortenerController::class)->group(function () {

    // YOUTUBE SHORTENER
    Route::get('/youtube', 'youtube')->name('youtube');
    Route::get('/create-youtube', 'createYoutube')->name('youtube.create');
    Route::post('/youtube-store', 'youtubeStore')->name('youtube.store');
    Route::get('/youtube-list', 'youtubeList')->name('youtube.list');
    Route::post('youtube-status', 'youtubeStatus')->name('youtube.status');
    Route::get('/delete-youtube/{id}', 'deleteYoutube')->name('youtube.delete');

    // URL SHORTENER

    Route::get('/index', 'index')->name('index');
    Route::get('/report', 'report')->name('report');
    Route::post('store', 'store')->name('store');
});