<?php

namespace Modules\ApluAnalytics\Http\Middleware;

use Closure;

class LicenseCheck
{
  public function handle($request, \Closure $next)
{
    $licenseKey = env('APLUANALYTICS_LICENSE_KEY');

    if ($licenseKey !== 'YOUR_VALID_LICENSE_KEY') {
        // Return error page as the entire response, so nothing else renders
        return response()->view('errors.module-unlicensed', [
            'moduleName' => 'Aplu Analytics'
        ], 403);
    }
    return $next($request);
}
}
