<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function generalSettings()
    {
        return view('settings.general');
    }

    public function emailSettings()
    {
        return view('settings.email');
    }

    public function serverInfo()
    {
        $info = [
            'php_version'        => phpversion(),
            'laravel_version'    => app()->version(),
            'environment'        => app()->environment(),
            'memory_limit'       => ini_get('memory_limit'),
            'max_execution_time'=> ini_get('max_execution_time'),
        ];
        return view('settings.server-info', compact('info'));
    }

    public function utilities()
    {
        return view('settings.utilities');
    }

    public function purgeCache()
    {
        Artisan::call('cache:clear');
        return back()->with('status', 'Application cache cleared.');
    }

    public function clearLog()
    {
        $log = storage_path('logs/laravel.log');
        if (File::exists($log)) {
            File::put($log, '');
        }
        return back()->with('status', 'Log file cleared.');
    }

    public function makeCache()
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        return back()->with('status', 'Config & route cache created.');
    }

    public function upgrade()
    {
        return view('settings.upgrade');
    }

    public function backupSubscribersPage()
    {
        return view('settings.backup-subscribers');
    }

    public function firebaseSetup()
    {
        return view('settings.firebase-setup');
    }
}
