<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\PushConfig;
use App\Models\Installation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InstallController
{
    
    public function installSetup()
    {
        $url = constant('license-push');
        if(! $url){
            return redirect()->back();
        }
        return view('install.setup',compact('url'));
    }
    
    public function postInstallSetup(Request $request)
    {
        // 1) Validation rules for both Push Configuration and License
        $rules = [
            // Push Config validation rules
            'service_account_json_file' => ['required', 'file', 'mimes:json', 'mimetypes:application/json'],
            'vapid_public_key' => ['required', 'string'],
            'vapid_private_key' => ['required', 'string'],
            'web_apiKey' => ['required', 'string'],
            'web_authDomain' => ['required', 'string'],
            'web_projectId' => ['required', 'string'],
            'web_messagingSenderId' => ['required', 'string'],
            'web_appId' => ['required', 'string'],
            'web_measurementId' => ['required', 'string'],
            'web_storageBucket' => ['required', 'string'],

            // License verification validation rules
            'license_code' => 'required|string|max:255',
            'domain_name' => 'required|string|max:255',
            'registered_email' => 'required|email|max:255',
            'license_verified' => 'required|in:1',
            'registered_username' => 'required|string|max:255',
        ];

        $messages = [
            // Push Config validation messages
            'service_account_json_file.required' => 'Please upload your Service Account JSON file.',
            'service_account_json_file.mimes' => 'The file must have a .json extension.',
            'service_account_json_file.mimetypes' => 'The file must be valid JSON (application/json).',
            'vapid_public_key.required' => 'Please enter your VAPID public key.',
            'vapid_private_key.required' => 'Please enter your VAPID private key.',
            'vapid_public_key.string' => 'VAPID public key must be a string.',
            'vapid_private_key.string' => 'VAPID private key must be a string.',
            'web_apiKey.required' => 'Firebase API key is required.',
            'web_authDomain.required' => 'Firebase authDomain is required.',
            'web_projectId.required' => 'Firebase projectId is required.',
            'web_messagingSenderId.required' => 'Messaging Sender ID is required.',
            'web_appId.required' => 'Firebase App ID is required.',
            'web_measurementId.required' => 'Firebase Measurement ID is required.',
            'web_storageBucket.required' => 'Firebase Storage Bucket is required.',

            // License verification validation messages
            'license_code.required' => 'Please provide a valid license code.',
            'domain_name.regex' => 'Please provide a valid domain name.',
            'registered_email.email' => 'Please provide a valid email address.',
            'registered_email.unique' => 'This email is already in use.',
        ];

        try {
            // 2) Validate both Push Configuration and License fields
            $validated = $request->validate($rules, $messages);

            // 3) Log validation success
            Log::info('Validation successful.', $validated);

            // 4) Handle Push Config
            $rawJson = file_get_contents($request->file('service_account_json_file')->getRealPath());
            Log::info('Service account JSON file loaded.');

            $webConfig = [
                'apiKey' => $validated['web_apiKey'],
                'authDomain' => $validated['web_authDomain'],
                'projectId' => $validated['web_projectId'],
                'storageBucket' => $validated['web_storageBucket'],
                'messagingSenderId' => $validated['web_messagingSenderId'],
                'appId' => $validated['web_appId'],
                'measurementId' => $validated['web_measurementId'],
            ];

           // 5) Decode & ensure required keys in service account JSON
            $data = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);
            Log::info('Decoded service account JSON.');

            foreach (['project_id', 'private_key', 'client_email'] as $key) {
                if (empty($data[$key])) {
                    Log::error("Missing required JSON key: {$key}");
                    throw new \Exception("Missing required JSON key: {$key}");
                }
            }

            // 5) Save Push Config securely
            $config = PushConfig::firstOrNew();
            $config->service_account_json = encrypt($rawJson);
            $config->vapid_public_key = $validated['vapid_public_key'];
            $config->vapid_private_key = encrypt($validated['vapid_private_key']);
            $config->web_app_config = encrypt(json_encode($webConfig));
            $config->save();

            Log::info('Push config saved successfully.');

            // 6) Handle License Verification and Save
            $domain_name = strtolower($validated['domain_name']);
            $licenseData = [
                'key' => $validated['license_code'],
                'domain' => $domain_name,
                'uname' => $validated['registered_username'],
                'uemail' => $validated['registered_email'],
            ];

            // Encrypt the license data
            $encryptedData = encrypt(json_encode($licenseData));

            // Store license info in the database securely
            Installation::updateOrCreate(
                ['id' => 1],
                [
                    'is_installed' => 1,
                    'data' => $encryptedData,
                ]
            );

            Log::info('License data saved successfully.');

            // Securely update the .env file with encrypted license details
            $this->updateEnvFile([
                'LICENSE_CODE' => encrypt($validated['license_code']),
                'APP_DOMAIN' => encrypt($domain_name),
                'LICENSE_USER' => encrypt($validated['registered_username']),
                'LICENSE_EMAIL' => encrypt($validated['registered_email']),
            ]);

            // Create a new user for the installation
            $email = $domain_name . '@gmail.com';
            $password = $domain_name . '@' . rand(999, 99999999);

            User::updateOrCreate(['id' => 1], [
                'name' => $validated['registered_username'],
                'email' => $email,
                'password' => Hash::make($password)
            ]);

            Log::info('Admin user created.', ['email' => $email, 'password' => $password]);

            $middlewares = [
                'RateLimitMiddleware',
                'CheckUserAccess',
                'DomainMiddleware',
                'EnsurePushConfig',
                'PermissionMiddleware',
            ];

            foreach ($middlewares as $middleware) {
                $filePath = md_dir($middleware);

                if (!File::exists($filePath)) {
                    continue;
                }

                $hash = hash_file('sha256', $filePath);

                DB::table('middleware')->updateOrInsert(
                    ['middleware' => $middleware],
                    ['token' => $hash]
                );
            }
             

            return view('install.complete', [
                'admin_email' => $email,
                'admin_password' => $password,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in postInstallSetup: ' . $e->getMessage(), ['exception' => $e]);
            // Handle any other errors
            return back()->withInput()->with('error', 'Failed to save configuration and license: ' . $e->getMessage());
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

    public function syncMiddlewareTokens(){
        $middlewares = [
            'RateLimitMiddleware',
            'CheckUserAccess',
            'DomainMiddleware',
            'EnsurePushConfig',
            'PermissionMiddleware',
        ];

        foreach ($middlewares as $middleware) {
            $filePath = md_dir($middleware);

            if (!File::exists($filePath)) {
                continue;
            }

            $hash = hash_file('sha256', $filePath);

            DB::table('middleware')->updateOrInsert(
                ['middleware' => $middleware],
                ['token' => $hash]
            );
        }
        return "done";
    }

}