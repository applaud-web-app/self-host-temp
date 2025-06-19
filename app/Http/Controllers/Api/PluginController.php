<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Domain;
use App\Models\DomainLicense;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PluginController extends Controller
{
    /**
     * Look up an active domain by its name.
     *
     * @param  string  $domainName
     * @return Domain|null
     */
    private function getValidDomain(string $domainName): ?Domain
    {
        return Domain::where('name', $domainName)->where('status', 1)->first();
    }
    
    /**
     * Verify that the provided raw key matches the stored hash + salt + pepper.
     *
     * @param  string          $providedKey
     * @param  DomainLicense   $license
     * @return bool
     */
    private function verifyDomainKey(string $providedKey, DomainLicense $license): bool
    {
        $pepper  = config('license.license_code');
        if (empty($pepper)) {
            // purgeMissingPepper();
            return false;
        }
        $toCheck = $license->salt . $providedKey . $pepper;
        return Hash::check($toCheck, $license->key_hash);
    }

    public function verifyLicenseKey(Request $request)
    {
        $clientIp   = $request->header('CF-Connecting-IP') ?? $request->getClientIp();
        $limiterKey = 'plugin-verify:' . $clientIp;
        $maxAttempts = 3;
        $lockSeconds = 300;

        // 1) block on 4th try, lock for 5 minutes
        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            $available = RateLimiter::availableIn($limiterKey);
            $minutes   = ceil($available / 60);
            return response()->json([
                'status'  => false,
                'message' => "Too many attempts. Try again in {$minutes} minute(s)."
            ], 429);
        }

        // 2) validation
        try {
            $data = $request->validate([
                'domain_name' => 'required|string|max:100',
                'key'         => 'required|string|max:200',
            ]);
        } catch (ValidationException $e) {
            // count as a “try”
            RateLimiter::hit($limiterKey, $lockSeconds);
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized request.'
            ], 401);
        }

        try {
            // 3) domain lookup
            $domain = $this->getValidDomain($data['domain_name']);
            if (! $domain) {
                RateLimiter::hit($limiterKey, $lockSeconds);
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized access.'
                ], 401);
            }

            // 4) license check
            $license = DomainLicense::where('domain_id', $domain->id)->latest('created_at')->first();

            if (! $license || ! $this->verifyDomainKey($data['key'], $license)) {
                RateLimiter::hit($limiterKey, $lockSeconds);
                return response()->json([
                    'status'  => false,
                    'message' => 'The provided license key is invalid.'
                ], 401);
            }
            
            // 5) already used?
            if($license->is_used){
                RateLimiter::hit($limiterKey, $lockSeconds);
                return response()->json([
                    'status'  => false,
                    'message' => 'This license key has already been used.'
                ], 401);
            }

            // 6) success — mark it used and clear the throttle
            $license->markUsed();
            RateLimiter::clear($limiterKey);

            return response()->json([
                'status'  => true,
                'message' => 'License key verified successfully.'
            ], 200);

        } catch (\Throwable $e) {
            RateLimiter::hit($limiterKey, $lockSeconds);
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized access.'
            ], 500);
        }
    }
    
}
