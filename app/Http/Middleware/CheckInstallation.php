<?php

// app/Http/Middleware/CheckInstallation.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\Installation;

class CheckInstallation
{
    public function handle(Request $request, Closure $next)
    {
        $isInstallRoute = $request->is('install*');
        if (! Schema::hasTable('installations')) {
            if (! $isInstallRoute) {
                return redirect()->route('install.welcome');
            }
            return $next($request);
        }

        // 2) If the table exists but there’s no installation row yet:
        $installation = Installation::latest('created_at')->first();
        if (! $installation) {
            if (! $isInstallRoute) {
                return redirect()->route('install.welcome');
            }
            return $next($request);
        }

        // 3) If we have a row but `is_installed` is still false, keep them in installer:
        if (! $installation->is_installed) {
            if (! $isInstallRoute) {
                return redirect()->route('install.welcome');
            }
            return $next($request);
        }

        // 4) If it *is* installed, block any future /install/* hits:
        if ($isInstallRoute) {
            return redirect()->route('login');
        }

        // 5) Otherwise let the request through:
        return $next($request);
    }
}
