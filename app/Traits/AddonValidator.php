<?php

namespace App\Traits;

use App\Models\Installation;
use App\Models\Addon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

trait AddonValidator
{

    protected function fail(string $message): array
    {
        Log::warning("Subscription validation warning: $message");
        return ['success' => false, 'message' => $message];
    }

    public function validateAddons(): array
    {
        // 1. Fetch current domain
        $currentDomain = $_SERVER['HTTP_HOST'];

        // 2. Fetch installation record
        $installation = Installation::first();
        if (! $installation) {
            return fail("No installation record found.");
        }

        // 3. Decrypt installation data
        try {
            $decrypted = decryptUrl($installation->data);
            $parsed = is_string($decrypted) ? json_decode($decrypted, true) : $decrypted;
        } catch (\Exception $e) {
            return $this->fail("Unable to decrypt installation data.");
        }

        // 4. Decrypt .env license and domain
        try {
            $envLicense = decryptUrl(config('license.LICENSE_CODE'));
        } catch (\Exception $e) {
            return $this->fail("Unable to decrypt LICENSE_CODE from .env.");
        }

        try {
            $envDomain = decryptUrl(config('license.APP_DOMAIN'));
        } catch (\Exception $e) {
            return $this->fail("Unable to decrypt APP_DOMAIN from .env.");
        }

        // 5. Validate license key
        $storedLicenseKey = $parsed['key'] ?? null;
        if (! $storedLicenseKey || $storedLicenseKey !== $envLicense) {
            return fail("License key mismatch.");
        }

        // 6. Validate domain
        $storedDomain = $parsed['domain'] ?? null;
        if ($currentDomain !== $storedDomain || $currentDomain !== $envDomain) {
            return fail("Domain mismatch: expected $storedDomain, got $currentDomain.");
        }

        $userName  = $parsed['uname'] ?? null;
        $userEmail = $parsed['uemail'] ?? null;

        // 7. Push URL constant
        $pushUrl = constant('addon-push');
        if (! $pushUrl) {
            return fail("Add-on push URL not defined.");
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
                    return fail("Remote: Invalid license key.");
                }

                return $this->fail("Remote validation failed with status {$response->status()}.");
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
            return $this->fail("Remote add-on fetch failed: " . $e->getMessage());
        }
    }

}