<?php

namespace Modules\AdvanceSegmentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Addon;
use Illuminate\Support\Facades\Artisan;

class CheckLicenseKey
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $providedKey = decrypt(config('license.advance_segmentation_key'));
            $providedName = config('license.advance_segmentation_name');
            $isInstalled = Addon::where('preferred_name', $providedName)->where('status', 'installed')->first();
            if (!$isInstalled) {
                Artisan::call('down');
            }
            $expectedKey = decrypt($isInstalled->addon_key);
            if (empty($providedKey) || $providedKey !== $expectedKey) {
                Artisan::call('down');
            }
        } catch (\Throwable $th) {
            Artisan::call('down');
        }

        return $next($request);
    }
}