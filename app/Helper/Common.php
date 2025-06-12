<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

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