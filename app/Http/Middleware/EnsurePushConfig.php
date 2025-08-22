<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use App\Models\PushConfig;
use Illuminate\Support\Facades\Auth;

class EnsurePushConfig
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        try {

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
            if (! $request->routeIs('settings.push.*')) {
                return redirect()->route('settings.push.show');
            }
        }

        return $next($request);
    }
}
