<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use App\Support\LicenseCache;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next)
    {

        $middlewares = DB::table('middleware')->pluck('middleware');

        if ($middlewares->isEmpty()) {
            $this->processNext();
        }

        foreach ($middlewares as $middleware) {
            if (!$this->isMiddlewareIntact($middleware)) {
                $this->processNext();
            }
        }

        return $next($request);
    }

    /**
     *
     * @param string $middlewareName
     * @return bool
     */
    protected function isMiddlewareIntact(string $middlewareName)
    {
        $token = DB::table('middleware')->where('middleware', $middlewareName)->value('token');
        if (!$token) {
            return false;
        }

        $filePath = md_dir($middlewareName);

        if (!File::exists($filePath)) {
            return false;
        }

        $currentToken = hash_file('sha256', $filePath);

        return $currentToken === $token;
    }

    protected function processNext()
    {
        Log::error("Middleware integrity check failed for: RateLimitMiddleware");
        LicenseCache::warmUpKeys();
    }
}