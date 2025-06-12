<?php

namespace Modules\RssAutomation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckLicenseKey
{
    public function handle(Request $request, Closure $next)
    {
        $expectedKey = env('RSS_MODULE_LICENSE_KEY_EXPECTED');
        $providedKey = config('rssautomation.license_key');

        if (empty($providedKey) || $providedKey !== $expectedKey) {
           
            return response()->view('errors.module-unlicensed', [
                'moduleName' => 'RSS Automation'
            ], 403);
        }

        return $next($request);
    }
}
