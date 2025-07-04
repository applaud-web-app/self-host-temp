<?php

namespace App\Traits;

use App\Models\Installation;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

trait SubscriptionValidator
{
    public function validateSubscription(): array
    {
        // 1. Check installation exists
        $installation = Installation::first();
        if (! $installation) {
            $this->fail("No installation record found.");
        }

        // 2. Check installed flag
        if ((int) $installation->is_installed !== 1) {
            $this->fail("System not installed properly.");
        }

        // 3. Decrypt installation data
        try {
            $decrypted = decryptUrl($installation->data);
            $parsed = is_string($decrypted) ? json_decode($decrypted, true) : $decrypted;
        } catch (\Exception $e) {
            $this->fail("Unable to decrypt installation data.");
        }

        // 4. Validate license key with .env
        try {
            $envLicense = decryptUrl(env('LICENSE_CODE'));
        } catch (\Exception $e) {
            $this->fail("Unable to decrypt LICENSE_CODE from .env.");
        }
        
        // 5. Domain ENV
        try {
            $envDomain = decryptUrl(env('APP_DOMAIN'));
        } catch (\Exception $e) {
            $this->fail("Unable to decrypt APP_DOMAIN from .env.");
        }

        $storedLicenseKey = $parsed['key'] ?? null;
        if (! $storedLicenseKey || $storedLicenseKey !== $envLicense) {
            $this->fail("License key mismatch.");
        }

        // 5. Validate domain
        $currentDomain = $_SERVER['HTTP_HOST'];
        $storedDomain = $parsed['domain'] ?? null;

        if ($currentDomain !== $storedDomain || $currentDomain !== $envDomain) {
            $this->fail("Domain mismatch: expected $storedDomain, got $currentDomain.");
        }

        $userName = $parsed['uname'] ?? null;
        $userEmail = $parsed['uemail'] ?? null;

        // 6. Get push URL from config or env
        $pushUrl = constant('subscription-push');
        if (! $pushUrl) {
            $this->fail("Subscription push URL not defined.");
        }

        // 7. Remote validation
        try {
            $response = Http::timeout(5)->post($pushUrl, [
                'license_key' => $envLicense,
                'domain'      => $currentDomain,
                'user_name'   => $userName,
                'user_email'  => $userEmail,
            ]);

            if (! $response->successful()) {
                $body = $response->json();
                if ($response->status() === 404 && isset($body['error']) && $body['error'] === 'Invalid license key.') {
                    $this->fail("Remote: Invalid license key.");
                }

                $this->fail("Remote validation failed with status {$response->status()}.");
            }

            $data = $response->json('data');
            $data['purchase_date'] = Carbon::createFromFormat('d-M-Y', $data['purchase_date']);

            return $data;

        } catch (\Exception $e) {
            $this->fail("Remote request failed: " . $e->getMessage());
        }
    }

    protected function fail(string $message): void
    {
        Log::critical("Subscription check failed: $message");
        Artisan::call('down');
        abort(503, 'System is down due to subscription validation failure.');
    }
}
