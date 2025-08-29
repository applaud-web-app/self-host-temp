<?php

namespace App\Services;

use App\Models\PushSubscriptionHead;
use App\Models\PushSubscriptionPayload;
use App\Models\PushSubscriptionMeta;

class SubscribersImporter
{
    public function importRow(array $row, string $domain): void
    {
        $token = $row['token'] ?? null;
        if (!$token) return;

        $head = PushSubscriptionHead::firstOrNew(['token' => $token]);
        $head->domain        = $domain;
        $head->parent_origin = $domain;
        $head->save();

        PushSubscriptionPayload::updateOrCreate(
            ['head_id' => $head->id],
            [
                'endpoint' => $row['endpoint'] ?? null,
                'auth'     => $row['auth'] ?? null,
                'p256dh'   => $row['p256dh'] ?? null,
            ]
        );

        PushSubscriptionMeta::updateOrCreate(
            ['head_id' => $head->id],
            [
                'ip_address'     => $row['ip'] ?? ($row['ip address'] ?? null),
                'status'         => $row['status'] ?? null,
                'subscribed_url' => $row['subscribed_url'] ?? ($row['url'] ?? null),
                'country'        => $row['country'] ?? null,
                'state'          => $row['state'] ?? null,
                'city'           => $row['city'] ?? null,
                'device'         => $row['device'] ?? null,
                'browser'        => $row['browser'] ?? null,
                'platform'       => $row['platform'] ?? null,
            ]
        );
    }
}
