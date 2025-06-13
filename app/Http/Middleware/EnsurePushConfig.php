<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
        // Cache for 6 hours
        [$hasJson, $hasVapid] = Cache::remember(
            'push_config_validity',
            now()->addHours(6),
            function () {
                try {
                    $cfg = PushConfig::first();

                    $rawJson   = $cfg ? decrypt($cfg->service_account_json) : '';
                    $hasJson   = ! empty($rawJson);
                    $rawPriv   = $cfg ? decrypt($cfg->vapid_private_key) : '';
                    $hasVapid  = $cfg && $cfg->vapid_public_key && ! empty($rawPriv);

                    return [$hasJson, $hasVapid];
                } catch (\Throwable $e) {
                    // If decryption or DB fails, log & treat as invalid
                    Log::error('Failed checking PushConfig validity', [
                        'error' => $e->getMessage(),
                    ]);
                    return [false, false];
                }
            }
        );

        if (! $hasJson || ! $hasVapid) {
            // Allow the user to hit your push-settings UI so they can fill in credentials
            if (! $request->routeIs('settings.push.*')) {
                return redirect()->route('settings.push.show');
            }
        }

        return $next($request);
    }
}
