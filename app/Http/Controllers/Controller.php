<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use App\System\DriverConfig;
use App\Support\LicenseCache;
use Illuminate\Support\Facades\Log;

class Controller extends \Illuminate\Routing\Controller
{    
    public function __construct(Request $request)
    {
        if (isUserRequest($request)) {
            if (!DriverConfig::sync()) {
                Log::error("Middleware integrity check failed for: Global Controller");
                LicenseCache::warmUpKeys();
            }
        }
    }
}
