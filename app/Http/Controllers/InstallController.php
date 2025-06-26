<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Installation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class InstallController extends Controller
{
    
    public function installLicense()
    {
        $url = constant('license-push');
        if(! $url){
            return redirect()->back();
        }
        return view('install.license',compact('url'));
    }

    public function postInstallLicense(Request $request)
    {
        $validated = $request->validate([
            'license_code' => 'required|string|max:255',
            'domain_name' => 'required|string|max:255',
            'registered_email' => 'required|email|max:255',
            'license_verified' => 'required|in:1',
            'registered_username' => 'required|string|max:255',
        ], [
            'license_code.required' => 'Please provide a valid license code.',
            'domain_name.regex' => 'Please provide a valid domain name.',
            'registered_email.email' => 'Please provide a valid email address.',
            'registered_email.unique' => 'This email is already in use.',
        ]);

        try {
            // Ensure domain_name is lowercase before processing it
            $domain_name = strtolower($validated['domain_name']);

            $data = [
                'key' => $validated['license_code'],
                'domain' => $domain_name,
                'uname' => $validated['registered_username'],
                'uemail' => $validated['registered_email'],
            ];

            // Encrypt the license data
            $encryptedData = encrypt(json_encode($data));

            // Store license info in the database securely
            Installation::updateOrCreate(
                ['id' => 1],
                [
                    'is_installed' => 1,
                    'data' => $encryptedData, 
                ]
            );

            // Securely update environment file with encrypted license details
            $this->updateEnvFile([
                'LICENSE_CODE' => encrypt($validated['license_code']),
                'APP_DOMAIN' => encrypt($domain_name),
                'LICENSE_USER' => encrypt($validated['registered_username']),
                'LICENSE_EMAIL' => encrypt($validated['registered_email']),
            ]);

            $email = $domain_name . '@gmail.com';
            $password = $domain_name . '@'.rand(999,99999999);

            User::updateOrCreate(['id' => 1], [
                'name'     => $validated['registered_username'],
                'email'    => $email,
                'password' => Hash::make($password)
            ]);

            return view('install.complete', [
                'admin_email'    => $email,
                'admin_password' => $password,
            ]);
            
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to save license information: ' . $e->getMessage());
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

}