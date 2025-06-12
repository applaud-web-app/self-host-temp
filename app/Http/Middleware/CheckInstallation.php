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

        $install = Installation::instance();

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
