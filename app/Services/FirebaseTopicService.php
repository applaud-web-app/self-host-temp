<?php

namespace App\Services;

use App\Models\PushConfig;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class FirebaseTopicService
{
    private $messaging;

    public function __construct()
    {
        $credentials = $this->getCachedCredentials();

        $this->messaging = (new Factory)
            ->withServiceAccount($credentials) // array, not file
            ->createMessaging();
    }

    /**
     * Cache ONLY credentials (safe & serializable)
     */
    private function getCachedCredentials(): array
    {
        return Cache::remember(
            'firebase.service_account.credentials',
            now()->addMinutes(10),
            function () {
                $config = PushConfig::query()->first();

                if (!$config) {
                    throw new RuntimeException('Firebase PushConfig not found');
                }

                $credentials = $config->credentials;

                if (empty($credentials)) {
                    throw new RuntimeException('Firebase credentials missing or invalid');
                }

                return $credentials; // ARRAY ONLY
            }
        );
    }

    /* ===================== TOPIC OPS ===================== */

    public function subscribe(string $token, string $topic): void
    {
        $this->messaging->subscribeToTopic(
            $this->normalizeTopic($topic),
            $token
        );
    }

    public function unsubscribe(string $token, string $topic): void
    {
        $this->messaging->unsubscribeFromTopic(
            $this->normalizeTopic($topic),
            $token
        );
    }

    // public function sendToTopic(array $payload): void
    // {
    //     $message = CloudMessage::fromArray($payload);
    //     $this->messaging->send($message);
    // }

    public function sendToTopic(array $payload): void
    {
        $message = CloudMessage::fromArray($payload);
        $result = $this->messaging->send($message);
    }

    public function subscribeBatch(array $tokens, string $topic): void
    {
        if (empty($tokens)) {
            return;
        }

        // Firebase limit
        $tokens = array_slice($tokens, 0, 1000);

        $this->messaging->subscribeToTopic(
            $this->normalizeTopic($topic),
            $tokens
        );
    }


    /* ===================== HELPERS ===================== */

    private function normalizeTopic(string $topic): string
    {
        $topic = strtolower(trim($topic));
        $topic = preg_replace('#^https?://#', '', $topic);
        return rtrim($topic, '/');
    }
}
