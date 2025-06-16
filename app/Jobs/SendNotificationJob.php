<?php

namespace App\Jobs;

use App\Models\PushConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries   = 1;
    public int $timeout = 3600;

    protected int $notificationId;

    public function __construct(int $notificationId)
    {
        $this->notificationId = $notificationId;
    }

    public function handle(): void
    {
        try {
            // 1) Load notification data minimal via DB
            $row = DB::table('notifications')
                ->select([
                    'title', 'description', 'banner_icon', 'banner_image',
                    'target_url', 'message_id',
                    'btn_1_title', 'btn_1_url',
                    'btn_title_2', 'btn_url_2'
                ])
                ->where('id', $this->notificationId)
                ->first();

            if (! $row) {
                Log::error("Notification {$this->notificationId} not found");
                return;
            }

            // 2) Load FCM config once
            $cfg = PushConfig::first();
            if (! $cfg) {
                Log::error("FCM config missing");
                return;
            }
            $factory = (new Factory())->withServiceAccount($cfg->credentials);

            // 3) Build payload array
            $webPushData = $this->buildWebPush($row);

            // 4) Atomically fetch pending domains with names, and mark queued
            $pending = DB::transaction(function () {
                $pending = DB::table('domain_notification as dn')
                    ->join('domains as d', 'dn.domain_id', '=', 'd.id')
                    ->where('dn.notification_id', $this->notificationId)
                    ->where('dn.status', 'pending')
                    ->lockForUpdate()
                    ->select('dn.domain_id', 'd.name as domain_name')
                    ->get();
                if ($pending->isNotEmpty()) {
                    $ids = $pending->pluck('domain_id')->all();
                    DB::table('domain_notification')
                        ->where('notification_id', $this->notificationId)
                        ->whereIn('domain_id', $ids)
                        ->update(['status' => 'queued', 'sent_at' => null]);
                }
                return $pending;
            });

            if ($pending->isEmpty()) {
                return; // nothing to dispatch
            }

            // 5) Dispatch domain jobs without N+1 queries
            $map = $pending->pluck('domain_name', 'domain_id');
            foreach ($map as $domainId => $domainName) {
                SendNotificationDomainJob::dispatch(
                    $this->notificationId,
                    $factory,
                    $webPushData,
                    $domainId,
                    $domainName
                );
            }

        } catch (Throwable $e) {
            Log::error("SendNotificationJob error [notif={$this->notificationId}]: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function buildWebPush(object $row): array
    {
        $base = [
            'title'        => $row->title,
            'body'         => $row->description,
            'icon'         => $row->banner_icon ?? '',
            'image'        => $row->banner_image ?? '',
            'click_action' => $row->target_url,
            'message_id'   => (string) $row->message_id,
        ];

        $actions = [];
        if ($row->btn_1_title && $row->btn_1_url) {
            $actions[] = ['action' => 'btn1', 'title' => $row->btn_1_title, 'url' => $row->btn_1_url];
        }
        if ($row->btn_title_2 && $row->btn_url_2) {
            $actions[] = ['action' => 'btn2', 'title' => $row->btn_title_2, 'url' => $row->btn_url_2];
        }
        if (count($actions) < 2) {
            $actions[] = ['action' => 'close', 'title' => 'Close'];
        }

        return [
            'data'    => array_merge(
                array_map('strval', $base),
                ['actions' => json_encode($actions)]
            ),
            'headers' => ['Urgency' => 'high'],
        ];
    }
}