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
    protected function fail(string $message): array
    {
        Log::warning("Subscription validation warning: $message");
        return ['success' => false, 'message' => $message];
    }

    public function validateSubscription(): array
    {
        // 1. Check installation exists
        $installation = Installation::first();
        if (! $installation) {
            return fail("No installation record found.");
        }

        // 2. Check installed flag
        if ((int) $installation->is_installed !== 1) {
            return fail("System not installed properly.");
        }

        // 3. Decrypt installation data
        try {
            $decrypted = decryptUrl($installation->data);
            $parsed = is_string($decrypted) ? json_decode($decrypted, true) : $decrypted;
        } catch (\Exception $e) {
            return $this->fail("Unable to decrypt installation data.");
        }

        // 4. Validate license key with .env
        try {
            $envLicense = decryptUrl(config('license.LICENSE_CODE'));
        } catch (\Exception $e) {
            return $this->fail("Unable to decrypt");
        }
        
        // 5. Domain ENV
        try {
            $envDomain = decryptUrl(config('license.APP_DOMAIN'));
        } catch (\Exception $e) {
            return $this->fail("Unable to decrypt");
        }

        $storedLicenseKey = $parsed['key'] ?? null;
        if (! $storedLicenseKey || $storedLicenseKey !== $envLicense) {
            return fail("License key mismatch.");
        }

        // 5. Validate domain
        // $currentDomain = $_SERVER['HTTP_HOST'];
        $currentDomain = request()->host();
        $hostName = host();

        if ($currentDomain !== $hostName) {
            $currentDomain = $hostName;
        }
        $storedDomain = $parsed['domain'] ?? null;
        $expectedIp = decrypt(config("license.SERVER_IP"));

        if (!in_array($currentDomain, ['localhost', $expectedIp, $storedDomain, $envDomain])) {
            return fail("Domain mismatch: expected $storedDomain, got $currentDomain.");
        }
        
        if ($storedDomain !== $envDomain) {
            return fail("Domain mismatch: stored $storedDomain, and env $envDomain.");
        }

        $userName = $parsed['uname'] ?? null;
        $userEmail = $parsed['uemail'] ?? null;

        // 6. Get push URL from config or env
        $pushUrl = constant('subscription-push');
        if (! $pushUrl) {
            return fail("Subscription URL not defined.");
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
                    fail("Remote: Invalid license key.");
                }

                return $this->fail("Remote validation failed with status {$response->status()}.");
            }

            $data = $response->json('data');
            $data['purchase_date'] = Carbon::createFromFormat('d-M-Y', $data['purchase_date']);

            return $data;

        } catch (\Exception $e) {
            return $this->fail("Remote request failed: " . $e->getMessage());
        }
    }
}
