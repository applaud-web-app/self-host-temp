<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Installation;
use Illuminate\Support\Facades\Auth;

class CheckInstallation
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        if (!isUserRequest($request)) {
            return $next($request);
        }

        if ($request->is('install/*')) {
            return $next($request);
        }
        
        try {
            $installation = Installation::updateOrCreate(
                ['id' => 1]
            );
        } catch (\Throwable $e) {   
            Installation::truncate(); 
            return redirect()->route('install.setup');
        }

        if (
            $installation
            && $installation->is_installed != 1
            && empty($installation->data)
        ) {
            return redirect()->route('install.setup');
        }

        return $next($request);
    }
}
