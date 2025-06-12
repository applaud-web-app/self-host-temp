<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PushConfigController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\NotificationController;


Route::prefix('install')->group(function () {
    // welcome
    Route::get('/',             [InstallController::class, 'installWelcome'])->name('install.welcome');
    Route::post('/',            [InstallController::class, 'postInstallWelcome']);

    // environment check
    Route::get('/environment',  [InstallController::class, 'installEnvironment'])->name('install.environment');
    Route::post('/environment', [InstallController::class, 'postInstallEnvironment'])->name('install.environment.post');

    // license
    Route::get('/license',      [InstallController::class, 'installLicense'])->name('install.license');
    Route::post('/license',     [InstallController::class, 'postInstallLicense'])->name('install.license.post');

    // database
    Route::get('/database',     [InstallController::class, 'installDatabase'])->name('install.database');
    Route::post('/database',    [InstallController::class, 'postInstallDatabase'])->name('install.database.post');

    // cron
    Route::get('/cron',         [InstallController::class, 'installCron'])->name('install.cron');
    Route::post('/cron',        [InstallController::class, 'postInstallCron'])->name('install.cron.post');

    // admin
    Route::get('/admin-setup',  [InstallController::class, 'adminSetup'])->name('install.admin-setup');
    Route::post('/admin-setup', [InstallController::class, 'postAdminSetup'])->name('install.admin-setup.post');

    // complete (GET only)
    Route::get('/complete',     [InstallController::class, 'installComplete'])->name('install.complete');
});

Route::get('/', [Controller::class, 'index'])->name('home');

// First page is login
Route::controller(AuthController::class)->group(function () {
    Route::get('login', 'login')->name('login');
    Route::post('login', 'doLogin')->name('login.doLogin');
});

// Optional: Home page (static)
Route::get('/dashboard', [Controller::class, 'dashboard'])->name('dashboard');

Route::middleware(['auth','ensure_push_config'])->group(function() {

    // Push Config routes
    Route::controller(PushConfigController::class)->prefix('settings/push')->name('settings.push.')->group(function () {
        Route::get('/', 'show')->name('show');
        Route::post('/save', 'save')->name('save');
    });

    // Domain routes
    Route::controller(DomainController::class)->prefix('domain')->name('domain.')->group(function () {
        Route::get('/', 'view')->name('view');
        Route::post('/create', 'create')->name('store');
        Route::post('check', 'check')->name('check');
        Route::post('/update-status', 'updateStatus')->name('update-status');
        Route::get('/integrate', 'integrate')->name('integrate');
        Route::get('/download-sw', 'downloadSW')->name('download-sw');
    });
    
    // Send Notification routes
    Route::controller(NotificationController::class)->prefix('notification')->name('notification.')->group(function () {
        Route::get('/','view')->name('view');
        Route::get('/create','create')->name('create');
        Route::post('/send','store')->name('send');
        Route::get('/{notification}','show')->name('show');
        Route::post('/{notification}/send','send')->name('resend');
        Route::post('/fetch-meta', 'fetchMeta')->name('fetchMeta');
    });


    // Route::get('/domain', [Controller::class, 'domain'])->name('domain');
    Route::get('/integrate-domain', [Controller::class, 'integrateDomain'])->name('integrate-domain');
    Route::get('/subscription', [Controller::class, 'subscription'])->name('subscription');
    Route::get('/send-notification', [Controller::class, 'sendNotification'])->name('send-notification');
    Route::get('/campaign-reports', [Controller::class, 'campaignReports'])->name('campaign-reports');
    Route::get('/profile', [Controller::class, 'profile'])->name('profile');

    // Settings routes (using the same Controller)
    Route::prefix('settings')->group(function () {
        Route::get('/general',       [Controller::class, 'generalSettings'])->name('settings.general');
        Route::get('/email',         [Controller::class, 'emailSettings'])->name('settings.email');
        Route::get('/server-info',   [Controller::class, 'serverInfo'])->name('settings.server-info');
        Route::get('/utilities',     [Controller::class, 'utilities'])->name('settings.utilities');
        Route::post('/utilities/purge-cache',  [Controller::class, 'purgeCache'])->name('settings.utilities.purge-cache');
        Route::post('/utilities/clear-log',    [Controller::class, 'clearLog'])->name('settings.utilities.clear-log');
        Route::post('/utilities/make-cache',   [Controller::class, 'makeCache'])->name('settings.utilities.make-cache');
        Route::get('/upgrade', [Controller::class, 'upgrade'])->name('settings.upgrade');
        Route::get('/backup-subscribers', [Controller::class, 'backupSubscribersPage'])->name('settings.backup-subscribers'); // Fixed naming
        Route::get('/firebase-setup', [Controller::class, 'firebaseSetup'])->name('settings.firebase-setup'); // Fixed naming
    });


    // Install Wizard Routes (views only)
    // Route::prefix('install')->group(function () {
    //     Route::get('/',                 [Controller::class, 'installWelcome'])->name('install.welcome');
    //     Route::get('/environment',      [Controller::class, 'installEnvironment'])->name('install.environment');
    //     Route::get('/license',          [Controller::class, 'installLicense'])->name('install.license');
    //     Route::get('/database',         [Controller::class, 'installDatabase'])->name('install.database');
    //     Route::get('/cron',             [Controller::class, 'installCron'])->name('install.cron');
    //     Route::get('/admin-setup',      [Controller::class, 'adminSetup'])->name('install.admin-setup');
    //     Route::get('/complete',         [Controller::class, 'installComplete'])->name('install.complete');
    // });

    Route::get('/addons', [Controller::class, 'addons'])->name('addons');

});





