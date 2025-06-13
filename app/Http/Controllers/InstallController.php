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

class InstallController extends Controller
{

    // protected function checkStep($requestedStep)
    // {
    //     $installation = Installation::firstOrCreate([], ['completed_step' => 0]);
        
    //     // If installation is complete, redirect to home
    //     if ($installation->is_installed) {
    //         return redirect('/');
    //     }

    //     // If requested step is higher than completed step + 1, redirect to next allowed step
    //     if ($requestedStep == $installation->completed_step) {
    //         return redirect()->route($this->getStepRoute($installation->completed_step + 1));
    //     }

    //     if($requestedStep < $installation->completed_step) {
    //         return redirect()->route($this->getStepRoute($installation->completed_step));

    //     }
        
    //     // If requested step is higher than completed step + 1, redirect to next allowed step
    //     if ($requestedStep > $installation->completed_step + 1) {
    //         return redirect()->route($this->getStepRoute($installation->completed_step + 1));
    //     }

    //     return null;
    // }

    // protected function getStepRoute($step)
    // {
    //     $routes = [
    //         1 => 'install.environment',
    //         2 => 'install.license',
    //         3 => 'install.database',
    //         4 => 'install.cron',
    //         5 => 'install.admin-setup',
    //         6 => 'install.complete',
    //     ];
        
    //     return $routes[$step] ?? 'install.welcome';
    // }


    protected function checkStep(int $requestedStep)
    {
        // grab-or-create our install record
        $installation = Installation::firstOrCreate([], ['completed_step' => 0]);

        // if the app is already fully installed, kick them to /
        if ($installation->is_installed) {
            return redirect('/');
        }

        // The only step they should ever be on is the next incomplete one:
        $nextStep = $installation->completed_step + 1;

        // if they try to go anywhere else, redirect them to that next step
        if ($requestedStep !== $nextStep) {
            return redirect()->route($this->getStepRoute($nextStep));
        }

        // otherwise—perfectly valid!
        return null;
    }

    /**
     * Maps a numeric step to its install route.
     */
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
            'installation_directory'  => is_dir(base_path()) && is_writable(base_path()),
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
            'redis'                  => true, //extension_loaded('redis')
            'supervisor'            => true, //shell_exec('pgrep supervisord') !== null
        ];

        $folders = [
            '.env file'                  => is_writable(base_path('.env')),
            '/storage/framework (dir)'   => is_writable(storage_path('framework')),
        ];

        // Check if all requirements are met
        $allRequirementsMet = !in_array(false, $requirements, true) && !in_array(false, $folders, true);

        return view('install.environment', compact('requirements', 'folders', 'allRequirementsMet'));
    }

    public function postInstallEnvironment(Request $request)
    {
        // Re-check all requirements in case someone bypasses the frontend
        $requirements = [
            'installation_directory'  => is_dir(base_path()) && is_writable(base_path()),
            'fileinfo'               => extension_loaded('fileinfo'),
            'json'                   => extension_loaded('json'),
            'tokenizer'              => extension_loaded('tokenizer'),
            'zip'                    => extension_loaded('zip'),
            'curl'                   => function_exists('curl_version'),
            'openssl'                => extension_loaded('openssl'),
            'php_version_(min_php_version:_7.2.0)' => version_compare(PHP_VERSION, '7.2.0', '>='),
            'ctype'                  => extension_loaded('ctype'),
            'mbstring'               => extension_loaded('mbstring'),
            'pdo'                    => extension_loaded('pdo'),
            'allow_url_fopen'        => (bool)ini_get('allow_url_fopen'),
            'redis'                  => true, // extension_loaded('redis')
            'supervisor'             => true, // shell_exec('pgrep supervisord') !== null
        ];

        $folders = [
            '.env file'                  => is_writable(base_path('.env')),
            '/storage/framework (dir)'   => is_writable(storage_path('framework')),
        ];

        // Check if all requirements are met
        $allRequirementsMet = !in_array(false, $requirements, true) && !in_array(false, $folders, true);

        if (!$allRequirementsMet) {
            // Prepare error messages for failed requirements
            $failedRequirements = array_filter($requirements, function($value) {
                return $value === false;
            });
            
            $failedFolders = array_filter($folders, function($value) {
                return $value === false;
            });

            $errorMessages = [];
            
            // Add failed requirements to messages
            foreach ($failedRequirements as $requirement => $status) {
                $errorMessages[] = ucwords(str_replace('_', ' ', $requirement)) . ' requirement not met';
            }
            
            // Add failed folders to messages
            foreach ($failedFolders as $folder => $status) {
                $errorMessages[] = $folder . ' is not writable';
            }

            return redirect()->route('install.environment')
                ->with('error', 'Please fix all requirements before continuing.')
                ->with('error_details', $errorMessages);
        }

        Installation::firstOrCreate([], [])->update(['completed_step' => max(1, Installation::first()->completed_step)]);
    
        // All requirements met - proceed to next step
        return redirect()->route('install.license')
            ->with('success', 'Environment requirements successfully validated.');
    }

    public function installLicense()
    {
        if ($redirect = $this->checkStep(2)) {
            return $redirect;
        }
        return view('install.license');
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
            // Store license info in database
            Installation::updateOrCreate(
                ['id' => 1], // Assuming you only have one installation record
                [
                    'license_key' => $validated['license_code'],
                    'licensed_domain' => $validated['domain_name'],
                    'completed_step' => 2
                ]
            );

            // define in config file that is more safer
            $this->updateEnvFile([
                'LICENSE_CODE' => $validated['license_code']
            ]);

            return redirect()->route('install.database')->with('success', 'License verified and saved successfully!');

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

            $escapedValue = $this->escapeEnvValue($raw);

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
    // STEP 3: Database Configuration
    //
    public function installDatabase()
    {
        if ($redirect = $this->checkStep(3)) {
            return $redirect;
        }
        return view('install.database');
    }

    // public function postInstallDatabase(Request $request)
    // {
    //     // Validate the request
    //     $validated = $request->validate([
    //         'db_host'     => 'required|string|max:255',
    //         'db_port'     => 'required|numeric|min:1|max:65535',
    //         'db_name'     => 'required|string|max:100',
    //         'db_username' => 'required|string|max:100',
    //         'db_password' => 'nullable|string|max:255',
    //     ]);

    //     try {
    //         $envUpdates = [
    //             'DB_HOST'     => $validated['db_host'],
    //             'DB_PORT'     => $validated['db_port'],
    //             'DB_DATABASE' => $validated['db_name'],
    //             'DB_USERNAME' => $validated['db_username'],
    //         ];

    //         // Only add password to updates if it exists in the validated data
    //         if (isset($validated['db_password'])) {
    //             $envUpdates['DB_PASSWORD'] = $validated['db_password'];
    //         }

    //         // Update .env file with database credentials
    //         $this->updateEnvFile($envUpdates);
            
    //         // Clear configuration cache to reload new values
    //         Artisan::call('config:clear');
            
    //         // Optional: Cache the new configuration
    //         if (app()->environment('production')) {
    //             Artisan::call('config:cache');
    //         }
            
    //         // Update installation progress
    //         Installation::updateOrCreate(
    //             ['id' => 1], // Assuming you only have one installation record
    //             [
    //                 'completed_step' => 3
    //             ]
    //         );
            
    //         return redirect()->route('install.cron')->with('success', 'Database configured successfully!');
            
    //     } catch (\Exception $e) {
    //         Log::error("message :" . $e->getMessage());
    //         return back()->with('error', 'Database configuration failed: ' . $e->getMessage());
    //     }
    // }

    public function postInstallDatabase(Request $request)
    {
        // Define strong validation rules
        $rules = [
            'db_host'     => ['required', 'string', 'max:255'],
            'db_port'     => ['required', 'integer', 'min:1', 'max:65535'],
            'db_name'     => ['required', 'string', 'alpha_dash', 'max:100'],
            'db_username' => ['required', 'string', 'alpha_dash', 'max:100'],
            'db_password' => ['nullable', 'string', 'max:255'],
            // In production, uncomment the next line and comment out the nullable rule above:
            // 'db_password' => ['required', 'string', 'max:255'],
        ];

        $validated = $request->validate($rules);

        try {
            $envUpdates = [
                'DB_HOST'     => $validated['db_host'],
                'DB_PORT'     => $validated['db_port'],
                'DB_DATABASE' => $validated['db_name'],
                'DB_USERNAME' => $validated['db_username'],
            ];

            if (isset($validated['db_password'])) {
                $envUpdates['DB_PASSWORD'] = $validated['db_password'];
            }

            // Update .env
            $this->updateEnvFile($envUpdates);

            // Refresh config
            Artisan::call('config:clear');
            if (app()->environment('production')) {
                Artisan::call('config:cache');
            }

            Installation::updateOrCreate(
                ['id' => 1],
                ['completed_step' => 3]
            );

            return redirect()->route('install.cron')
                            ->with('success', 'Database configured successfully!');

        } catch (\Exception $e) {
            Log::error("Env write error: " . $e->getMessage());
            return back()->with('error', 'Database configuration failed: ' . $e->getMessage());
        }
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

        // Mark the application as installed
        Installation::firstOrCreate([], [])
            ->update(['is_installed' => true]);

        // Pass credentials to the final step
        session([
            'installed_admin_email'    => $u['admin_email'],
            'installed_admin_password' => $u['admin_password'],
        ]);

        Installation::firstOrCreate([], [])->update(['completed_step' => max(1, Installation::first()->completed_step)]);
        return redirect()->route('install.complete');
    }

    //
    // STEP 6: Completion Screen
    //
    public function installComplete()
    {
        $email    = session('installed_admin_email');
        $password = session('installed_admin_password');

        return view('install.complete', [
            'admin_email'    => $email,
            'admin_password' => $password,
        ]);
    }
}