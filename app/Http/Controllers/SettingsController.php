<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\GeneralSetting;
use App\Models\EmailSetting;
use App\Models\Backupsub;
use App\Models\PushConfig;
use App\Models\PushSubscriptionPayload;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\DB;


class SettingsController extends Controller
{
    /**
     * Display the Email Settings form.
     */
    public function emailSettings()
    {
        $email = EmailSetting::first();
        return view('settings.email', compact('email'));
    }

    /**
     * Handle submission of Email Settings.
     * Writes values into .env, refreshes config cache, and updates the DB.
     */
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

            // 1) Update the .env file
            $this->updateEnvFile($envUpdates);

            // Refresh config
            Artisan::call('config:clear');
            if (app()->environment('production')) {
                Artisan::call('config:cache');
            }

            // 3) Persist to database
            EmailSetting::first()->update($data);

            return back()->with('success', 'Email settings updated!');
        } catch (\Throwable $th) {
            return back()->with('error', 'Something went wrong!');
        }
    }
    
    /* --------------------------------------------------------------------- */
    /*  Server Info & Metrics                                                */
    /* --------------------------------------------------------------------- */
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

        $extensions = get_loaded_extensions();
        sort($extensions, SORT_STRING);

        return view('settings.server-info', compact(
            'info', 'totalDisk', 'freeDisk', 'usedDisk', 'diskPercent', 'extensions'
        ));
    }

    public function serverMetrics()
    {
        /* ---------------- CPU % ---------------- */
        $load  = 0;
        if (PHP_OS_FAMILY !== 'Windows' && function_exists('sys_getloadavg')) {
            $load = sys_getloadavg()[0] ?? 0;
        }
        
        $cores = 1;
        if (PHP_OS_FAMILY === 'Linux') {
            if (is_readable('/proc/cpuinfo')) {
                preg_match_all('/^processor\s*:/m', file_get_contents('/proc/cpuinfo'), $m);
                $cores = max(1, count($m[0]));
            }
        } elseif (PHP_OS_FAMILY === 'Darwin' && function_exists('shell_exec')) {
            $cores = (int) trim(shell_exec('sysctl -n hw.ncpu') ?: 1);
        } elseif (PHP_OS_FAMILY === 'Windows') {
            $cores = (int) (getenv('NUMBER_OF_PROCESSORS') ?: 1);
        }

        $cpu = min(100, ($cores ? $load / $cores : $load) * 100);

        /* ---------------- Memory % ------------- */
        $memory = 0;

        if (is_readable('/proc/meminfo')) {                   // Linux
            $mem = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/',     $mem, $tot);
            preg_match('/MemAvailable:\s+(\d+)/', $mem, $avail);
            $total = (int) ($tot[1] ?? 0) * 1024;
            $free  = (int) ($avail[1] ?? 0) * 1024;
            $memory = $total ? (($total - $free) / $total) * 100 : 0;

        } elseif (PHP_OS_FAMILY === 'Darwin' && function_exists('shell_exec')) { // macOS
            $total = (int) trim(shell_exec('sysctl -n hw.memsize') ?: 0);
            $vm    = shell_exec('vm_stat');
            preg_match('/Pages free:\s+(\d+)/', $vm, $f);
            $free  = ((int) ($f[1] ?? 0)) * 4096;
            $memory = $total ? (($total - $free) / $total) * 100 : 0;

        } elseif (PHP_OS_FAMILY === 'Windows' && function_exists('shell_exec')) {
            $out = shell_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value');
            preg_match('/TotalVisibleMemorySize=(\d+)/', $out, $tot);
            preg_match('/FreePhysicalMemory=(\d+)/',     $out, $free);
            $total = (float) ($tot[1] ?? 0);
            $avail = (float) ($free[1] ?? 0);
            $memory = $total ? (($total - $avail) / $total) * 100 : 0;
        }

        return response()->json([
            'cpu'    => round($cpu, 2),
            'memory' => round($memory, 2),
        ]);
    }


    /**
     * Display the Utilities page.
     */
    public function utilities()
    {
        return view('settings.utilities');
    }

    /**
     * Purge application cache.
     */
    public function purgeCache()
    {
        Artisan::call('cache:clear');
        return back()->with('status', 'Application cache cleared.');
    }

    /**
     * Clear the laravel.log file.
     */
    public function clearLog()
    {
        $log = storage_path('logs/laravel.log');
        if (File::exists($log)) {
            File::put($log, '');
        }
        return back()->with('status', 'Log file cleared.');
    }

    /**
     * Generate config & route cache.
    */
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

    /**
     * Display the Upgrade page.
     */
    public function upgrade()
    {
        return view('settings.upgrade');
    }

    /**
     * Show only the latest subscriber backup.
    */
    public function backupSubscribers()
    {
        $latestBackup = Backupsub::latest()->first(); 
        return view('settings.backup-subscribers', compact('latestBackup'));
    }

 public function downloadBackupSubscribers()
{
    $config = PushConfig::first();
    $subs   = PushSubscriptionPayload::all();

    $publicKey = $config->vapid_public_key ?? '';
    $privateKey = decrypt($config->vapid_private_key) ?? '';

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

    // 1. Ensure the backups folder exists (creates if missing, no harm if already exists)
    File::ensureDirectoryExists(dirname($fullPath));

    // 2. Remove previous backup files to keep only the latest backup
    File::cleanDirectory(dirname($fullPath));

    // 3. Save the new backup file
    (new FastExcel($rows))->export($fullPath);

    // 4. Remove all old backup records from the database to store only the latest one
    Backupsub::truncate();

    // 5. Store only the latest backup record in the database
    Backupsub::create([
        'filename' => $filename,
        'count'    => $rows->count(),
        'path'     => $relativePath,
    ]);

    // 6. Return the download response
    return response()->download($fullPath, $filename, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ]);
}

    /**
     * Safely write key=>value pairs into the .env file.
     * Creates a timestamped backup and preserves existing lines.
     */
    protected function updateEnvFile(array $values)
    {
        $envPath = base_path('.env');

        // Ensure .env exists
        if (!file_exists($envPath)) {
            if (file_exists(base_path('.env.example'))) {
                copy(base_path('.env.example'), $envPath);
            } else {
                touch($envPath);
            }
        }

        $envContent = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            // Normalize null to empty string
            $raw = $value === null ? '' : (string)$value;

            // Force quoting for MAIL_PASSWORD, else use normal escaping
            if ($key === 'MAIL_PASSWORD') {
                // escape backslashes and double-quotes, then wrap
                $escapedValue = '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $raw) . '"';
            } else {
                $escapedValue = $this->escapeEnvValue($raw);
            }

            // Safely escape key for regex
            $quotedKey = preg_quote($key, '/');
            $pattern    = "/^{$quotedKey}=.*/m";
            $replacement = "{$key}={$escapedValue}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                // Append with proper newline
                $envContent .= PHP_EOL . $replacement;
            }
        }

        // Write atomically
        file_put_contents($envPath, $envContent, LOCK_EX);
    }

    protected function escapeEnvValue(string $value): string
    {
        // Empty becomes ""
        if ($value === '') {
            return '""';
        }

        // If it has whitespace or special chars, wrap and escape
        if (preg_match('/[\s"\'\\\\$`]/', $value)) {
            $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
            return "\"{$escaped}\"";
        }

        return $value;
    }

    
        public function viewLog()
        {
            // Define the log file path (adjust if necessary)
            $logFile = storage_path('logs/laravel.log');
            
            // Check if the log file exists
            if (File::exists($logFile)) {
                // Get the contents of the log file (or use tail for large logs)
                $logContent = File::get($logFile);
                
                // Optionally, you can paginate or limit the output
                // $logContent = implode("\n", array_slice(explode("\n", $logContent), -100)); // Get the last 100 lines
                
                return view('settings.view-log', compact('logContent'));
            } else {
                return response()->json(['error' => 'Log file not found'], 404);
            }
        }


}
