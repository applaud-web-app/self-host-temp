<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use App\Models\GeneralSetting;
use App\Models\EmailSetting;



class SettingsController extends Controller
{
   public function generalSettings()
    {
        // will always have one row
        $setting = GeneralSetting::first();
        return view('settings.general', compact('setting'));
    }

    // Handle the POST
    public function updateGeneralSettings(Request $request)
    {
        $data = $request->validate([
            'site_name'    => 'required|string|max:255',
            'site_url'     => 'required|url|max:255',
            'site_tagline' => 'nullable|string|max:255',
        ]);

        GeneralSetting::first()->update($data);

        return back()->with('status', 'General settings updated!');
    }

   public function emailSettings()
    {
        $email = EmailSetting::first();
        return view('settings.email', compact('email'));
    }

    /** Handle email settings update */
    public function updateEmailSettings(Request $request)
    {
        $data = $request->validate([
            'mail_driver'       => 'required|string|max:50',
            'mail_host'         => 'nullable|string|max:255',
            'mail_port'         => 'nullable|integer',
            'mail_username'     => 'nullable|string|max:255',
            'mail_password'     => 'nullable|string|max:255',
            'mail_encryption'   => 'nullable|string|max:50',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name'    => 'nullable|string|max:255',
        ]);

        EmailSetting::first()->update($data);

        return back()->with('status', 'Email settings updated!');
    }
    public function serverInfo()
    {
        // Static “App & Server” info
        $info = [
            'php_version'         => phpversion(),
            'laravel_version'     => app()->version(),
            'environment'         => app()->environment(),
            'memory_limit'        => ini_get('memory_limit'),
            'max_execution_time'  => ini_get('max_execution_time'),
            'server_software'     => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'os'                  => php_uname('s').' '.php_uname('r'),
        ];

        // Disk usage (for your project folder)
        $totalDisk   = (float) disk_total_space(base_path());
        $freeDisk    = (float) disk_free_space(base_path());
        $usedDisk    = $totalDisk - $freeDisk;
        $diskPercent = $totalDisk > 0
            ? round($usedDisk / $totalDisk * 100, 1)
            : 0;

        // PHP extensions
        $extensions = get_loaded_extensions();
        sort($extensions, SORT_STRING);

        return view('settings.server-info', compact(
            'info',
            'totalDisk',
            'freeDisk',
            'usedDisk',
            'diskPercent',
            'extensions'
        ));
    }

    public function serverMetrics()
    {
        // CPU %
        $load  = sys_getloadavg()[0];
        $cores = (int) trim(shell_exec('nproc') ?: 1);
        $cpu   = min(100, ($load / $cores) * 100);

        // Memory %
        $meminfo    = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)\skB/',     $meminfo, $tot);
        preg_match('/MemAvailable:\s+(\d+)\skB/', $meminfo, $avail);
        $totalBytes = $tot[1] * 1024;
        $availBytes = $avail[1] * 1024;
        $memory     = min(100, (($totalBytes - $availBytes) / $totalBytes) * 100);

        return response()->json([
            'cpu'    => round($cpu, 2),
            'memory' => round($memory, 2),
        ]);
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
