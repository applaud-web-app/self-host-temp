<?php

// app/Http/Middleware/CheckInstallation.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\Installation;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class CheckInstallation
{
   public function handle(Request $request, Closure $next)
    {
        $isInstallRoute = $request->is('install*');

        // 1) If they’re already hitting /install/*, just let them through.
        if ($isInstallRoute) {
            return $next($request);
        }

        // 2)  Try to connect to the DB and check for the table.
        try {                   // throws if no DB or bad creds
            $tableExists = Schema::hasTable('installations'); // throws if DB exists but no rights
        } catch (QueryException $e) {
            // Couldn’t connect or no rights or no database → go to installer
            return redirect()->route('install.welcome');
        }

        // 3) If the table simply doesn’t exist, installer
        if (! $tableExists) {
            return redirect()->route('install.welcome');
        }

        // 4) If table exists, try to fetch your installation record
        try {
            $installation = Installation::latest('created_at')->first();
        } catch (\Throwable $e) {
            // Something went wrong querying the table → installer
            return redirect()->route('install.welcome');
        }

        // 5) If no record yet, installer
        if (! $installation) {
            return redirect()->route('install.welcome');
        }

        // 6) If the record says is_installed = false, installer
        if (! $installation->is_installed) {
            return redirect()->route('install.welcome');
        }

        return $next($request);
    }
}
