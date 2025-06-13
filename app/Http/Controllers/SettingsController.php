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

class SettingsController extends Controller
{
    /**
     * Display the General Settings form.
     */
    public function generalSettings()
    {
        $setting = GeneralSetting::first();
        return view('settings.general', compact('setting'));
    }

    /**
     * Handle submission of General Settings.
     */
    public function updateGeneralSettings(Request $request)
    {
        $data = $request->validate([
            'site_name'    => 'required|string|max:255',
            'site_url'     => 'required|url|max:255',
            'site_tagline' => 'nullable|string|max:255',
        ]);

        GeneralSetting::first()->update($data);

        return back()->with([
            'status'      => 'General settings updated!',
            'status_type' => 'success',
        ]);
    }

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

    /**
     * Display the Server Info page.
     */
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
            'info','totalDisk','freeDisk','usedDisk','diskPercent','extensions'
        ));
    }

    /**
     * Return real-time CPU & memory metrics as JSON.
     */
    public function serverMetrics()
    {
        $load  = sys_getloadavg()[0];
        $cores = (int) trim(shell_exec('nproc') ?: 1);
        $cpu   = min(100, ($load / $cores) * 100);

        $meminfo    = File::get('/proc/meminfo');
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

    /**
     * Display the Upgrade page.
     */
    public function upgrade()
    {
        return view('settings.upgrade');
    }

    /**
     * List all previous subscriber backups.
     */
    public function backupSubscribers()
    {
        $backups = Backupsub::latest()->get();
        return view('settings.backup-subscribers', compact('backups'));
    }

    /**
     * Generate and download an XLSX backup of push subscribers,
     * then record it in the database.
     */
    public function downloadBackupSubscribers()
    {
        $config = PushConfig::first();
        $subs   = PushSubscriptionPayload::all();

        $rows = $subs->map(fn($s) => [
            'VAPID Public Key'  => $config->vapid_public_key,
            'VAPID Private Key' => $config->vapid_private_key,
            'Endpoint'          => $s->endpoint,
            'Auth'              => $s->auth,
            'P256DH'            => $s->p256dh,
        ]);

        $timestamp = now()->format('Ymd_His');
        $filename  = "subscribers_backup_{$timestamp}.xlsx";
        $path      = "backups/{$filename}";

        (new FastExcel($rows))
            ->export(storage_path("app/{$path}"));

        Backupsub::create([
            'filename' => $filename,
            'count'    => $rows->count(),
            'path'     => $path,
        ]);

        return response()->download(
            storage_path("app/{$path}"),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Display the Firebase Setup page.
     */
    public function firebaseSetup()
    {
        return view('settings.firebase-setup');
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

}
