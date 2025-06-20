<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use App\Models\Installation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;

class InstallController extends Controller
{
    protected function checkStep(int $requestedStep)
    {

        // 1) If they’re already in the installer, let them through
        // if (request()->is('install*')) {
        //     return null;
        // }

        // 2) Try to connect + check for the table; any exception = installer
        try {
            if (! Schema::hasTable('installations')) {   
                return null;
            }
        } catch (QueryException $e) {
           return null;
        }

        $installation = Installation::firstOrCreate(['id' => 1],['completed_step' => 0]);
        if ($installation->is_installed) {
            return redirect('/');
        }
        $nextStep = $installation->completed_step + 1;
        if ($requestedStep !== $nextStep) {
            return redirect()->route($this->getStepRoute($nextStep));
        }
        return null;
    }

    protected function getStepRoute(int $step): string
    {
        $routes = [
            0 => 'install.welcome',
            1 => 'install.environment',
            2 => 'install.license',
            3 => 'install.database',
            4 => 'install.cron',
            5 => 'install.admin-setup',
            6 => 'install.complete',
        ];

        // fallback to welcome if something weird happens
        return $routes[$step] ?? 'install.welcome';
    }

    //
    // STEP 0: Welcome Screen
    //
    public function installWelcome()
    {
        return view('install.welcome');
    }

    public function postInstallWelcome(Request $request)
    {
        return redirect()->route('install.environment');
    }

    //
    // STEP 1: Environment Check
    //
    public function installEnvironment()
    {
        if ($redirect = $this->checkStep(1)) {
            return $redirect;
        }

        $requirements = [
            'installation_directory' => is_dir(base_path()) && is_writable(base_path()),
            'fileinfo'               => extension_loaded('fileinfo'),
            'json'                   => extension_loaded('json'),
            'tokenizer'              => extension_loaded('tokenizer'),
            'zip'                    => extension_loaded('zip'),
            'curl'                   => function_exists('curl_version'),
            'openssl'                => extension_loaded('openssl'),
            'php_version'            => version_compare(PHP_VERSION, '7.2.0', '>='),
            'ctype'                  => extension_loaded('ctype'),
            'mbstring'               => extension_loaded('mbstring'),
            'pdo'                    => extension_loaded('pdo'),
            'allow_url_fopen'        => ini_get('allow_url_fopen'),
        ];

        $folders = [
            '.env file'                  => is_writable(base_path('.env')),
            '/storage/framework (dir)'   => is_writable(storage_path('framework')),
        ];

        $allOK = ! in_array(false, $requirements, true) && ! in_array(false, $folders, true);
        return view('install.environment', compact('requirements', 'folders', 'allOK'));
    }

    public function postInstallEnvironment(Request $request)
    {
        // Re-check all requirements in case someone bypasses the frontend
        
        $requirements = [
            'installation_directory' => is_dir(base_path()) && is_writable(base_path()),
            'fileinfo'               => extension_loaded('fileinfo'),
            'json'                   => extension_loaded('json'),
            'tokenizer'              => extension_loaded('tokenizer'),
            'zip'                    => extension_loaded('zip'),
            'curl'                   => function_exists('curl_version'),
            'openssl'                => extension_loaded('openssl'),
            'php_version'            => version_compare(PHP_VERSION, '7.2.0', '>='),
            'ctype'                  => extension_loaded('ctype'),
            'mbstring'               => extension_loaded('mbstring'),
            'pdo'                    => extension_loaded('pdo'),
            'allow_url_fopen'        => ini_get('allow_url_fopen'),
        ];

        $folders = [
            '.env file'                  => is_writable(base_path('.env')),
            '/storage/framework (dir)'   => is_writable(storage_path('framework')),
        ];

        $allOK = ! in_array(false, $requirements, true) && ! in_array(false, $folders, true);

        if (! $allOK) {
            return redirect()->route('install.environment')->with('error', 'Please fix all requirements before continuing.');
        }

        setInstallerData(['step' => 1]);
    
        return redirect()->route('install.database')->with('success', 'Environment looks good!');
    }

    public function installDatabase()
    {
        if ($redirect = $this->checkStep(2)) {
            return $redirect;
        }

        return view('install.database');
    }

    public function postInstallDatabase(Request $request)
    {
        $validated = $request->validate([
            'db_host'     => 'required|string',
            'db_port'     => 'required|integer',
            'db_name'     => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        $envUpdates = [
            'DB_HOST'     => $validated['db_host'],
            'DB_PORT'     => $validated['db_port'],
            'DB_DATABASE' => $validated['db_name'],
            'DB_USERNAME' => $validated['db_username'],
        ];

        if (isset($validated['db_password'])) {
            $envUpdates['DB_PASSWORD'] = $validated['db_password'];
        }
        
        if (app()->environment('production')) {
            $this->updateEnvFile($envUpdates);

            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Exception $e) {
                \Log::error("Migration failed during installer: ".$e->getMessage());
                return back()->withInput()->withErrors(['db' => 'Database migration failed: '.$e->getMessage()]);
            }
        }

        
        // 6) Mark step complete and redirect
        setInstallerData(['step' => 2]);
        $installer = getInstallerData();
        Installation::updateOrCreate(
            ['id' => 1],
            ['completed_step' => $installer['step'] ?? 2]
        );
        clearInstallerData();

        return redirect()->route('install.license')->with('success', 'Database configured and migrations complete.');
    }

    public function installLicense()
    {
        if ($redirect = $this->checkStep(3)) {
            return $redirect;
        }
        $url = constant('license-push');
        if(! $url){
            return redirect()->back();
        }
        return view('install.license',compact('url'));
    }

    public function postInstallLicense(Request $request)
    {
        $validated = $request->validate([
            'license_code' => 'required|string|min:10',
            'domain_name' => 'required|string|max:255',
            'registered_email' => 'required|email|max:255',
            'license_verified' => 'required|in:1',
            'registered_username' => 'required|string|max:255'
        ]);

        try {

            $encryptedLicense = Crypt::encryptString($validated['license_code']);
            $encryptedDomain  = Crypt::encryptString($validated['domain_name']);

            // Store license info in database
            Installation::updateOrCreate(
                ['id' => 1], // single installation record
                [
                    'license_key'     => $encryptedLicense,
                    'licensed_domain' => $encryptedDomain,
                    'completed_step'  => 3,
                ]
            );

            // define in config file that is more safer
            $this->updateEnvFile([
                'LICENSE_CODE' => $encryptedLicense
            ]);

            return redirect()->route('install.cron')->with('success', 'License verified and saved successfully!');

        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to save license information: ' . $e->getMessage());
        }
    }

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

            // Force quoting for DB_PASSWORD, else use normal escaping
            if ($key === 'DB_PASSWORD') {
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

    //
    // STEP 4: Cron Setup
    //
    public function installCron()
    {
        if ($redirect = $this->checkStep(4)) {
            return $redirect;
        }
        return view('install.cron');
    }

    public function postInstallCron(Request $request)
    {
        Installation::updateOrCreate(
            ['id' => 1], // Assuming you only have one installation record
            [
                'completed_step' => 4
            ]
        );
        return redirect()->route('install.admin-setup');
    }

    //
    // STEP 5: Admin Account Setup
    //
    public function adminSetup()
    {
        if ($redirect = $this->checkStep(5)) {
            return $redirect;
        }
        return view('install.admin-setup');
    }

    public function postAdminSetup(Request $request)
    {
        $u = $request->validate([
            'admin_email'                 => 'required|email|unique:users,email',
            'admin_password'              => 'required|confirmed|min:8',
        ]);

        // Create the super-admin user
        User::create([
            'name'   => 'Admin',
            'email'    => $u['admin_email'],
            'password' => Hash::make($u['admin_password']),
            'is_admin' => true,
        ]);

        Installation::firstOrCreate([], [])->update(['is_installed' => true,'completed_step' => max(1, Installation::first()->completed_step)]);

        return view('install.complete', [
            'admin_email'    => $u['admin_email'],
            'admin_password' => $u['admin_password'],
        ]);
    }

    //
    // STEP 6: Completion Screen
    //
    public function installComplete(Request $request)
    {
        return view('install.complete', [
            'admin_email'    => $email,
            'admin_password' => $password,
        ]);
    }

    public function getInstallComplete()
    {
        return redirect()->route('home')->with('success', '🎉 Account Created Successfully!! 🎉');
    }
}