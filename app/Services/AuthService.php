<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     *
     * @param string $email
     * @param string $password
     * @param bool $remember
     * @return mixed
     */
    public function attemptLogin($email, $password, $remember)
    {
        $decryptedDomain = $this->getDecryptedDomain();
        $emailDomain = $this->extractDomainFromEmail($email);

        // Check if email domain matches the expected domain
        if ($this->isDomainMatching($emailDomain, $decryptedDomain)) {
            return Auth::attempt(['email' => $email, 'password' => $password], $remember);
        }

        return false;
    }

    /**
     *
     * @return string
     */
    private function getDecryptedDomain()
    {
        return decrypt(env('APP_DOMAIN'));
    }

    /**
     *
     * @param string $email
     * @return string
     */
    private function extractDomainFromEmail($email)
    {
        return strstr($email, '@', true);
    }

    /**
     *
     * @param string $emailDomain
     * @param string $decryptedDomain
     * @return bool
     */
    private function isDomainMatching($emailDomain, $decryptedDomain)
    {
        return $emailDomain === $decryptedDomain;
    }
}
