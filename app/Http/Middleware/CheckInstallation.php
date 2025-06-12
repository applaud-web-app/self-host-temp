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
        if (! Schema::hasTable('installations') && ! $request->is('install*')) {
            return redirect()->route('install.welcome');
        }

        try {
            $install = Installation::first();
        } catch (\Illuminate\Database\QueryException $e) {
            // Table doesn’t exist (or other DB error) ⇒ send to installer
            if (! $request->is('install*')) {
                return redirect()->route('install.welcome');
            }
            // allow the install routes through
            return $next($request);
        }


        // If not yet installed, send everything (except /install/*) to the installer:
        if (! $install->is_installed && ! $request->is('install*')) {
            return redirect()->route('install.welcome');
        }

        // If already installed, block access to /install/*:
        if ($install->is_installed && $request->is('install*')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
