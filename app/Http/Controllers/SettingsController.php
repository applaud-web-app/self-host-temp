<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\GeneralSetting;
use App\Models\EmailSetting;
use App\Models\Backupsub;
use App\Models\PushConfig;
use App\Models\PushSubscriptionPayload;
use App\Models\Setting;
use Rap2hpoutre\FastExcel\FastExcel;

class SettingsController extends Controller
{
    /* ---------------------------------------------------------------------
     * Settings
     * --------------------------------------------------------------------- */
    public function viewSettings()
    {
        $setting = Setting::firstOrCreate(['id' => 1], [
            'batch_size' => 100,
            'daily_cleanup' => false,
        ]);

        $setting->sending_speed = match ((int) $setting->batch_size) {
            100 => 'slow',
            250 => 'medium',
            500 => 'fast',
            default => 'slow'
        };

        return view('settings.setting', compact('setting'));
    }

    public function postSettings(Request $request)
    {
        try {
            $data = $request->validate([
                'sending_speed' => ['required', 'in:slow,medium,fast'],
                'daily_cleanup' => ['nullable','boolean'],
            ]);

            $batchSize = match ($data['sending_speed']) {
                'slow' => 100,
                'medium' => 250,
                'fast' => 500,
                default => 100
            };

            cache()->forget('daily_cleanup_setting');
            cache()->forget('settings_batch_size');

            $setting = Setting::firstOrCreate(['id' => 1]);
            $setting->fill([
                'batch_size' => $batchSize,
                'daily_cleanup' => (bool) ($data['daily_cleanup'] ?? false),
            ])->save();

            return back()->with('success', 'Settings saved.');
        } catch (\Throwable $th) {
            return back()->with('error', 'Failed to save settings. ' . $th->getMessage());
        }
    }

    /* ---------------------------------------------------------------------
     * Authentication
     * --------------------------------------------------------------------- */
    public function logoutAllDevices(Request $request)
    {
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->delete();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Logged out from all devices.');
    }

    /* ---------------------------------------------------------------------
     * Email Settings
     * --------------------------------------------------------------------- */
    public function emailSettings()
    {
        $email = EmailSetting::first();
        return view('settings.email', compact('email'));
    }

    public function updateEmailSettings(Request $request)
    {
        try {
            $data = $request->validate([
                'mail_driver'       => 'required|string|max:50',
                'mail_host'         => 'required|string|max:255',
                'mail_port'         => 'required|integer',
                'mail_username'     => 'required|string|max:255',
                'mail_password'     => 'required|string|max:255',
                'mail_encryption'   => 'nullable|string|max:50',
                'mail_from_address' => 'required|email|max:255',
                'mail_from_name'    => 'required|string|max:255',
            ]);

            $envUpdates = [
                'MAIL_MAILER'       => $data['mail_driver'],
                'MAIL_HOST'         => $data['mail_host'],
                'MAIL_PORT'         => $data['mail_port'],
                'MAIL_USERNAME'     => $data['mail_username'],
                'MAIL_PASSWORD'     => $data['mail_password'],
                'MAIL_ENCRYPTION'   => $data['mail_encryption'] ?? '',
                'MAIL_FROM_ADDRESS' => $data['mail_from_address'],
                'MAIL_FROM_NAME'    => $data['mail_from_name'],
            ];

            $this->updateEnvFile($envUpdates);

            Artisan::call('config:clear');
            if (app()->environment('production')) {
                Artisan::call('config:cache');
            }

            $email = EmailSetting::first();
            if ($email) {
                $email->update($data);
            } else {
                EmailSetting::create($data);
            }

            return back()->with('success', 'Email settings updated!');
        } catch (\Throwable $th) {
            return back()->with('error', 'Something went wrong!');
        }
    }


    /* ---------------------------------------------------------------------
     * Server Info & Metrics
     * --------------------------------------------------------------------- */
    public function serverInfo()
    {
        $info = [
            'php_version'        => phpversion(),
            'laravel_version'    => app()->version(),
            'environment'        => app()->environment(),
            'memory_limit'       => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'server_software'    => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'os'                 => php_uname('s').' '.php_uname('r'),
        ];

        $totalDisk   = (float) disk_total_space(base_path());
        $freeDisk    = (float) disk_free_space(base_path());
        $usedDisk    = $totalDisk - $freeDisk;
        $diskPercent = $totalDisk > 0 ? round($usedDisk / $totalDisk * 100, 1) : 0;

        list($totalMemory, $usedMemory, $memoryPercent) = $this->getSystemMemoryUsage();

        $extensions = get_loaded_extensions();
        sort($extensions, SORT_STRING);

        return view('settings.server-info', compact(
            'info',
            'totalDisk', 'freeDisk', 'usedDisk', 'diskPercent',
            'extensions',
            'totalMemory', 'usedMemory', 'memoryPercent'
        ));
    }

    public function serverMetrics()
    {
        $cpuUsage = 0;
        if (is_readable('/proc/stat')) {
            $cpuUsage = $this->getCpuUsage();
        }

        $memory = $this->getSystemMemoryUsage();

        // Load averages (1m, 5m, 15m)
        $load = [0,0,0];
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
        }

        return response()->json([
            'cpu'    => round($cpuUsage, 2),   // CPU %
            'memory' => round($memory[2], 2),  // Memory %
            'load_1' => round($load[0], 2),    // Only 1-minute load avg
        ]);
    }

    private function getCpuUsage(): float
    {
        $stat1 = file('/proc/stat');
        usleep(500000); // sample over 0.5s
        $stat2 = file('/proc/stat');

        $cpu1 = explode(" ", preg_replace("!cpu +!", "", $stat1[0]));
        $cpu2 = explode(" ", preg_replace("!cpu +!", "", $stat2[0]));

        $info1 = array_sum($cpu1);
        $info2 = array_sum($cpu2);

        $difTotal = $info2 - $info1;
        $difIdle  = $cpu2[3] - $cpu1[3];

        return $difTotal > 0 ? ($difTotal - $difIdle) / $difTotal * 100 : 0;
    }

    private function getSystemMemoryUsage()
    {
        $memory = 0;
        $totalMemory = 0;
        $usedMemory = 0;

        if (is_readable('/proc/meminfo')) {
            $mem = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $mem, $tot);
            preg_match('/MemAvailable:\s+(\d+)/', $mem, $avail);
            $totalMemory = (int) ($tot[1] ?? 0) * 1024;
            $freeMemory  = (int) ($avail[1] ?? 0) * 1024;
            $usedMemory  = $totalMemory - $freeMemory;
            $memory = $totalMemory ? ($usedMemory / $totalMemory) * 100 : 0;
        }

        return [$totalMemory, $usedMemory, round($memory, 2)];
    }

    /* ---------------------------------------------------------------------
     * Utilities
     * --------------------------------------------------------------------- */
    public function utilities()
    {
        return view('settings.utilities');
    }

    public function purgeCache()
    {
        Artisan::call('optimize:clear');
        return back()->with('status', 'Application cache optimized and cleared.');
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

    public function queueManage()
    {
        Artisan::call('queue:clear');
        Artisan::call('queue:restart');
        return back()->with('status', 'Queue cleared, and workers restarted.');
    }

    public function upgrade()
    {
        return view('settings.upgrade');
    }

    /* ---------------------------------------------------------------------
     * Backups
     * --------------------------------------------------------------------- */
    public function backupSubscribers()
    {
        $latestBackup = Backupsub::latest()->first();
        return view('settings.backup-subscribers', compact('latestBackup'));
    }

    public function downloadBackupSubscribers()
    {
        $config = PushConfig::first();
        $subs   = PushSubscriptionPayload::all();

        $publicKey  = $config->vapid_public_key ?? '';
        $privateKey = $config->vapid_private_key ? decrypt($config->vapid_private_key) : '';

        $rows = $subs->map(fn($s) => [
            'public_key'  => $publicKey,
            'private_key' => $privateKey,
            'endpoint'    => $s->endpoint,
            'auth'        => $s->auth,
            'p256dh'      => $s->p256dh,
        ]);

        $timestamp = now()->format('Ymd_His');
        $filename  = "subscribers_backup_{$timestamp}.xlsx";
        $relativePath = "backups/{$filename}";
        $fullPath = storage_path("app/public/{$relativePath}");

        File::ensureDirectoryExists(dirname($fullPath));
        File::cleanDirectory(dirname($fullPath));

        (new FastExcel($rows))->export($fullPath);

        Backupsub::truncate();
        Backupsub::create([
            'filename' => $filename,
            'count'    => $rows->count(),
            'path'     => $relativePath,
        ]);

        return response()->download($fullPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }

    /* ---------------------------------------------------------------------
     * Helpers
     * --------------------------------------------------------------------- */
    protected function updateEnvFile(array $values)
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            if (file_exists(base_path('.env.example'))) {
                copy(base_path('.env.example'), $envPath);
            } else {
                touch($envPath);
            }
        }

        $envContent = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $raw = $value === null ? '' : (string) $value;

            if ($key === 'MAIL_PASSWORD') {
                $escapedValue = '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $raw) . '"';
            } else {
                $escapedValue = $this->escapeEnvValue($raw);
            }

            $quotedKey = preg_quote($key, '/');
            $pattern = "/^{$quotedKey}=.*/m";
            $replacement = "{$key}={$escapedValue}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= PHP_EOL . $replacement;
            }
        }

        file_put_contents($envPath, $envContent, LOCK_EX);
    }

    protected function escapeEnvValue(string $value): string
    {
        if ($value === '') {
            return '""';
        }
        if (preg_match('/[\s"\'\\\\$`]/', $value)) {
            $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
            return "\"{$escaped}\"";
        }
        return $value;
    }

    public function viewLog()
    {
        $logFile = storage_path('logs/laravel.log');
        if (File::exists($logFile)) {
            $logContent = File::get($logFile);
            $logContent = implode("\n", array_slice(explode("\n", $logContent), -1000));
            return view('settings.view-log', compact('logContent'));
        } else {
            return response()->json(['error' => 'Log file not found'], 404);
        }
    }
}
