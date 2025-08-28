<?php

namespace App\Services;

use App\Models\PushSubscriptionHead;
use App\Models\PushSubscriptionPayload;
use App\Models\PushSubscriptionMeta;

class SubscribersImporter
{
    /**
     * Import a single normalized row for a domain.
     * $row keys should be lowercase + trimmed.
     */
    public function importRow(array $row, string $domain): void
    {
        $token = $row['token'] ?? null;
        if (!$token) {
            // skip if no token
            return;
        }

        $endpoint = $row['endpoint'] ?? null;
        $auth     = $row['auth'] ?? null;
        $p256dh   = $row['p256dh'] ?? null;
        $ip       = $row['ip'] ?? ($row['ip address'] ?? null);
        $status   = $row['status'] ?? null;
        $url      = $row['subscribed_url'] ?? ($row['url'] ?? null);
        $device   = $row['device'] ?? null;
        $browser  = $row['browser'] ?? null;
        $platform = $row['platform'] ?? null;
        $country  = $row['country'] ?? null;
        $state    = $row['state'] ?? null;
        $city     = $row['city'] ?? null;

        // Head
        $head = PushSubscriptionHead::firstOrNew(['token' => $token]);
        $head->domain        = $domain;
        $head->parent_origin = $domain;
        $head->save();

        // Payload
        PushSubscriptionPayload::updateOrCreate(
            ['head_id' => $head->id],
            [
                'endpoint' => $endpoint,
                'auth'     => $auth,
                'p256dh'   => $p256dh,
            ]
        );

        // Meta
        PushSubscriptionMeta::updateOrCreate(
            ['head_id' => $head->id],
            [
                'ip_address'     => $ip,
                'status'         => $status,
                'subscribed_url' => $url,
                'country'        => $country,
                'state'          => $state,
                'city'           => $city,
                'device'         => $device,
                'browser'        => $browser,
                'platform'       => $platform,
            ]
        );
    }
}
