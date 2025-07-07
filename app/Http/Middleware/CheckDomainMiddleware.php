<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use App\Support\LicenseCache;

class CheckDomainMiddleware
{
    protected $middlewareFilePath;
    protected $storedHash;

    public function __construct()
    {
        // The path to the current middleware file
        $this->middlewareFilePath = base_path('app\Http\Middleware\CheckDomainMiddleware.php');
        
        // The stored hash (this should be securely stored in the environment)
        // $this->storedHash = env('CHECK_DOMAIN_MIDDLEWARE_HASH');
    }

    public function handle(Request $request, Closure $next)
    {
        // First, check if the file integrity is intact
        if (!$this->isFileIntact()) {
            // Log the file tampering and initiate self-destruction
            Log::critical("Middlewaresss file tampered with. Self-destruction triggered.");
            $this->selfDestruct();
        }

        // Proceed with domain validation logic
        $expectedDomain = decrypt(env('APP_DOMAIN'));

        if ($_SERVER['HTTP_HOST'] !== $expectedDomain && $_SERVER['HTTP_HOST'] !== 'localhost') {
            // Log the mismatch and initiate self-destruction
            Log::critical("Domain mismatch detected. Self-destruction triggered.");
            $this->selfDestruct();
        }

        return $next($request);
    }

    /**
     * Check if the middleware file has been altered.
     *
     * @return bool
     */
    protected function isFileIntact()
    {
        // Check if the file exists
        if (!File::exists($this->middlewareFilePath)) {
            return false;
        }

        // Calculate the current hash of the middleware file
        $currentHash = hash_file('sha256', $this->middlewareFilePath);

        // Compare the current hash with the stored hash
        return $currentHash === $this->storedHash;
    }

    /**
     * Trigger the self-destruction process.
    */
    protected function selfDestruct()
    {
        Log::error("check DOmain Middleware");
        // LicenseCache::warmUpKeys();
    }
}