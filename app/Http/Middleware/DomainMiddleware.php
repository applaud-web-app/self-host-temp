<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\VerifyDomain;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DomainMiddleware
{
    use VerifyDomain;

    /**
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        Log::info('"verify-domain" -- Domain verification started.');

        // Check if 'verify_user' constant is defined and log it
        $db_var = "verify_user";
        if (!defined($db_var)) {
            Log::error('"verify-domain" --- verify_user is not defined');
            $this->purgeCache();
        }

        // Get the constant value
        $checkVal = constant('verify_user');
        Log::info('"verify-domain" -- Fetching expected domain using constant:', ['verify_user' => $checkVal]);

        // Decrypt and log the expected domain
        $expectedDomain = decrypt(config("license.$checkVal"));
        Log::info('"verify-domain" -- Decrypted expected domain:', ['expectedDomain' => $expectedDomain]);

        // Compare the domain and log the result
        $currentDomain = $_SERVER['HTTP_HOST'];
        Log::info('"verify-domain" -- Current HTTP_HOST:', ['currentDomain' => $currentDomain]);

        if ($currentDomain !== $expectedDomain && $currentDomain !== 'localhost') {
            Log::error('"verify-domain" --- HTTP_HOST mismatch. Expected: ' . $expectedDomain . ', Found: ' . $currentDomain);
            $this->purgeCache();
        }

        return $next($request);
    }

    /**
     * Run optimize:clear to purge the cache and log the action.
     */
    protected function purgeCache()
    {
        Log::info('"verify-domain" -- Running optimize:clear to purge cache.');
        Artisan::call('optimize:clear');
        Log::info('"verify-domain" -- Cache purged successfully.');
    }
}
