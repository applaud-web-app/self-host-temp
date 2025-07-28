<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use App\Models\PushConfig;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\DomainMiddleware;
use App\Http\Middleware\CheckUserAccess;
use App\Http\Middleware\RateLimitMiddleware;

class EnsurePushConfig
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        if (!isUserRequest($request)) {
            return $next($request);
        }

        try {

            // (new PermissionMiddleware())->handle($request, function ($request) {});
            // (new DomainMiddleware())->handle($request, function ($request) {});
            // (new CheckUserAccess())->handle($request, function ($request) {});
            // (new RateLimitMiddleware())->handle($request, function ($request) {});

            $cfg = PushConfig::first();

            $rawJson  = $cfg ? decrypt($cfg->service_account_json) : '';
            $hasJson  = ! empty($rawJson);

            $rawPriv  = $cfg ? decrypt($cfg->vapid_private_key) : '';
            $hasVapid = $cfg && $cfg->vapid_public_key && ! empty($rawPriv);
        } catch (\Throwable $e) {
            Log::error('Failed checking PushConfig validity', [
                'error' => $e->getMessage(),
            ]);
            $hasJson  = false;
            $hasVapid = false;
        }

        if (! $hasJson || ! $hasVapid) {
            // Let them fill in credentials on the push-settings UI
            if (! $request->routeIs('settings.push.*')) {
                return redirect()->route('settings.push.show');
            }
        }

        return $next($request);
    }
}
