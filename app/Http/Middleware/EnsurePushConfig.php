<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\PushConfig;

class EnsurePushConfig
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cfg = PushConfig::first();
        $hasJson = $cfg && !empty(decrypt($cfg->service_account_json) ?? '');
        $hasVapid = $cfg && $cfg->vapid_public_key && !empty(decrypt($cfg->vapid_private_key) ?? '');

        if (! $hasJson || ! $hasVapid) {
            // allow access to the push settings routes
            if (! $request->routeIs('settings.push.*')) {
                return redirect()->route('settings.push.show');
            }
        }

        return $next($request);
    }
}
