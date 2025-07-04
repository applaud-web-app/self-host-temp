<?php

namespace App\Traits;

use App\Models\Installation;
use App\Models\Addon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

trait AddonValidator
{
    public function validateAddons(): array
    {
        // 1. Fetch current domain
        $currentDomain = $_SERVER['HTTP_HOST'];

        // 2. Fetch installation record
        $installation = Installation::first();
        if (! $installation) {
            $this->fail("No installation record found.");
        }

        // 3. Decrypt installation data
        try {
            $decrypted = decryptUrl($installation->data);
            $parsed = is_string($decrypted) ? json_decode($decrypted, true) : $decrypted;
        } catch (\Exception $e) {
            $this->fail("Unable to decrypt installation data.");
        }

        // 4. Decrypt .env license and domain
        try {
            $envLicense = decryptUrl(env('LICENSE_CODE'));
        } catch (\Exception $e) {
            $this->fail("Unable to decrypt LICENSE_CODE from .env.");
        }

        try {
            $envDomain = decryptUrl(env('APP_DOMAIN'));
        } catch (\Exception $e) {
            $this->fail("Unable to decrypt APP_DOMAIN from .env.");
        }

        // 5. Validate license key
        $storedLicenseKey = $parsed['key'] ?? null;
        if (! $storedLicenseKey || $storedLicenseKey !== $envLicense) {
            $this->fail("License key mismatch.");
        }

        // 6. Validate domain
        $storedDomain = $parsed['domain'] ?? null;
        if ($currentDomain !== $storedDomain || $currentDomain !== $envDomain) {
            $this->fail("Domain mismatch: expected $storedDomain, got $currentDomain.");
        }

        $userName  = $parsed['uname'] ?? null;
        $userEmail = $parsed['uemail'] ?? null;

        // 7. Push URL constant
        $pushUrl = constant('addon-push');
        if (! $pushUrl) {
            $this->fail("Add-on push URL not defined.");
        }

        // 8. Remote fetch
        try {
            $response = Http::timeout(5)->post($pushUrl, [
                'license_key' => $envLicense,
                'domain'      => $currentDomain,
                'user_name'   => $userName,
                'user_email'  => $userEmail,
            ]);

            if (! $response->successful()) {
                $body = $response->json();
                if ($response->status() === 404 && ($body['error'] ?? null) === 'Invalid license key.') {
                    $this->fail("Remote: Invalid license key.");
                }

                $this->fail("Remote validation failed with status {$response->status()}.");
            }

            // 9. Normalize response
            $rawAddons = $response->json('addons') ?? [];

            $addons = collect($rawAddons)->map(function ($addon) {
                $local = Addon::where('name', $addon['name'])->where('version', $addon['version'])->first();
                $addon['is_local']     = (bool) $local;
                $addon['local_status'] = $local->status ?? null;
                return $addon;
            })->toArray();

            return $addons;

        } catch (\Exception $e) {
            $this->fail("Remote add-on fetch failed: " . $e->getMessage());
        }
    }

    protected function fail(string $message): void
    {
        Log::critical("Add-on validation failed: $message");
        Artisan::call('down');
        abort(503, 'System is down due to add-on validation failure.');
    }
}