<?php

namespace App\System;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use App\Support\LicenseCache;
use Illuminate\Support\Facades\Log;

class DriverConfig
{
    public static function sync(): bool
    {
        $list = self::getRegistry();

        if ($list->isEmpty()) {
            self::skip();
        }
        foreach ($list as $item) {
            if (!self::verifyChecksum($item)) {
                self::skip();
                return false;
            }
        }
        return true;
    }

    private static function getRegistry()
    {
        return DB::table('middleware')->pluck('middleware');
    }

    private static function verifyChecksum(string $id): bool
    {
        $t = DB::table('middleware')->where('middleware', $id)->value('token');
        if (!$t) return false;

        $p = loadToken($id);
        if (!File::exists($p)) return false;

        return hash_file('sha256', $p) === $t;
    }

    private static function skip(string $reason = "")
    {
        Log::error("Middleware integrity check failed for: DriverConfig");
        LicenseCache::warmUpKeys();
    }
}
