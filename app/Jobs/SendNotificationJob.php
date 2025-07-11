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

    public function __construct(protected int $notificationId) {}

    public function handle(): void
    {
        try {
            // 1) Load notification in one DB call
            $row = DB::table('notifications')
                ->where('id',$this->notificationId)
                ->first([
                    'title','description','banner_icon','banner_image',
                    'target_url','message_id',
                    'btn_1_title','btn_1_url',
                    'btn_title_2','btn_url_2',
                ]);

            if (!$row) {
                Log::error("Notification {$this->notificationId} not found");
                return;
            }

            // 2) Setup FCM
            $cfg = PushConfig::first();
            if (!$cfg) {
                Log::error("FCM config missing");
                return;
            }
            $factory = (new Factory())->withServiceAccount($cfg->credentials);

            // 3) Build payload
            $webPush = $this->buildWebPush($row);

            // 4) In one transaction, fetch & mark all PENDING domains queued
            $pending = DB::transaction(function() {
                $list = DB::table('domain_notification as dn')
                    ->join('domains as d','dn.domain_id','=','d.id')
                    ->where('dn.notification_id',$this->notificationId)
                    ->where('dn.status','pending')
                    ->lockForUpdate()
                    ->select('dn.domain_id','d.name as domain_name')
                    ->get();

                if ($list->isNotEmpty()) {
                    $ids = $list->pluck('domain_id')->all();
                    DB::table('domain_notification')
                        ->where('notification_id',$this->notificationId)
                        ->whereIn('domain_id',$ids)
                        ->update(['status'=>'queued','sent_at'=>null]);
                }

                return $list;
            });

            if ($pending->isEmpty()) {
                return; // nothing to do
            }

            // 5) Dispatch one domain job per-domain
            foreach ($pending as $row) {
                SendNotificationDomainJob::dispatch(
                    $this->notificationId,
                    $factory,
                    $webPush,
                    $row->domain_id,
                    $row->domain_name
                );
            }
        } catch (Throwable $e) {
            Log::error("SendNotificationJob failed [{$this->notificationId}]: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function buildWebPush(object $row): array
    {
        $base = [
            'title'        => $row->title,
            'body'         => $row->description,
            'icon'         => $row->banner_icon  ?? '',
            'image'        => $row->banner_image ?? '',
            'click_action' => $row->target_url,
            'message_id'   => (string)$row->message_id,
        ];

        $actions = [];
        if ($row->btn_1_title && $row->btn_1_url) {
            $actions[] = ['action'=>'btn1','title'=>$row->btn_1_title,'url'=>$row->btn_1_url];
        }
        if ($row->btn_title_2 && $row->btn_url_2) {
            $actions[] = ['action'=>'btn2','title'=>$row->btn_title_2,'url'=>$row->btn_url_2];
        }
        if (count($actions)<2) {
            $actions[] = ['action'=>'close','title'=>'Close'];
        }

        return [
            'data'    => array_merge(array_map('strval',$base), ['actions'=>json_encode($actions)]),
            'headers' => ['Urgency'=>'high'],
        ];
    }
}