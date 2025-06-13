<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use App\Models\GeneralSetting;
use App\Models\EmailSetting;
use App\Models\Backupsub;
use App\Models\PushConfig;
use App\Models\PushSubscriptionPayload;
use Rap2hpoutre\FastExcel\FastExcel;

class SettingsController extends Controller
{
    public function generalSettings()
    {
        $setting = GeneralSetting::first();
        return view('settings.general', compact('setting'));
    }

    public function updateGeneralSettings(Request $request)
    {
        $data = $request->validate([
            'site_name'    => 'required|string|max:255',
            'site_url'     => 'required|url|max:255',
            'site_tagline' => 'nullable|string|max:255',
        ]);

        GeneralSetting::first()->update($data);

        return back()->with([
            'status' => 'General settings updated!',
            'status_type' => 'success'
        ]);
    }

    public function emailSettings()
    {
        $email = EmailSetting::first();
        return view('settings.email', compact('email'));
    }

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

        return back()->with([
            'status' => 'Email settings updated!',
            'status_type' => 'success'
        ]);
    }

    public function serverInfo()
    {
        $info = [
            'php_version'        => phpversion(),
            'laravel_version'    => app()->version(),
            'environment'        => app()->environment(),
            'memory_limit'       => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'server_software'    => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'os'                 => php_uname('s') . ' ' . php_uname('r'),
        ];

        $totalDisk   = (float) disk_total_space(base_path());
        $freeDisk    = (float) disk_free_space(base_path());
        $usedDisk    = $totalDisk - $freeDisk;
        $diskPercent = $totalDisk > 0 ? round($usedDisk / $totalDisk * 100, 1) : 0;

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
        $load  = sys_getloadavg()[0];
        $cores = (int) trim(shell_exec('nproc') ?: 1);
        $cpu   = min(100, ($load / $cores) * 100);

        $meminfo    = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)\skB/', $meminfo, $tot);
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

        return back()->with([
            'status' => 'Application cache cleared.',
            'status_type' => 'success'
        ]);
    }

    public function clearLog()
    {
        $log = storage_path('logs/laravel.log');

        if (File::exists($log)) {
            File::put($log, '');
        }

        return back()->with([
            'status' => 'Log file cleared.',
            'status_type' => 'success'
        ]);
    }

    public function makeCache()
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');

        return back()->with([
            'status' => 'Config & route cache created.',
            'status_type' => 'success'
        ]);
    }

    public function upgrade()
    {
        return view('settings.upgrade');
    }


  public function backupSubscribers()
{
    $backups = Backupsub::latest()->get();
    return view('settings.backup-subscribers', compact('backups'));
}

    /**
     * Generate & download CSV, then record it.
     */
   public function downloadBackupSubscribers()
{
    // 1) Load global VAPID keys
    $config = PushConfig::first();

    // 2) Load all subscription payloads
    $subs = PushSubscriptionPayload::all();

    // 3) Map into rows with human-readable headers
    $rows = $subs->map(fn($s) => [
        'VAPID Public Key'  => $config->vapid_public_key,
        'VAPID Private Key' => $config->vapid_private_key,
        'Endpoint'          => $s->endpoint,
        'Auth'              => $s->auth,
        'P256DH'            => $s->p256dh,
    ]);

    // 4) Build filename & storage path as .xlsx
    $timestamp = now()->format('Ymd_His');
    $filename  = "subscribers_backup_{$timestamp}.xlsx";
    $path      = "backups/{$filename}";

    // 5) Export XLSX to storage/app/backups/…
    (new FastExcel($rows))
        ->export(storage_path("app/{$path}"));

    // 6) Record the export in DB
    Backupsub::create([
        'filename' => $filename,
        'count'    => $rows->count(),
        'path'     => $path,
    ]);

    // 7) Stream the file for download
    return response()->download(storage_path("app/{$path}"), $filename, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ]);
}

    public function firebaseSetup()
    {
        return view('settings.firebase-setup');
    }
}
