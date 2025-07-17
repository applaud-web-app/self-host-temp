<?php

namespace Modules\RssAutomation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Addon;

class CheckLicenseKey
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $providedKey = decrypt(config('license.rss_key'));
            $isInstalled = Addon::where('name', 'Rss Addon')->where('status', 'installed')->first();
            if (!$isInstalled) {
                zephyrStateCryp();
            }
            $expectedKey = decrypt($isInstalled->addon_key);
            if (empty($providedKey) || $providedKey !== $expectedKey) {
                zephyrStateCryp();
            }
        } catch (\Throwable $th) {
            zephyrStateCryp();
        }

        return $next($request);
    }
}