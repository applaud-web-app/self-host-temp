<?php

// App/Jobs/SendSegmentNotificationJob.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSegmentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries   = 1;
    public int $timeout = 3600;

    public function __construct(
        protected int $notificationId,
        protected int $segmentId
    ) {}

    public function handle(): void
    {
        try {
            $row = DB::table('notifications')
                ->where('id', $this->notificationId)
                ->first([
                    'title','description','banner_icon','banner_image',
                    'target_url','message_id',
                    'btn_1_title','btn_1_url',
                    'btn_title_2','btn_url_2',
                ]);

            if (! $row) {
                Log::error("Notification {$this->notificationId} not found");
                return;
            }

            $webPush = $this->buildWebPush($row);

            $pending = DB::transaction(function() {
                $list = DB::table('domain_notification as dn')
                    ->join('domains as d', 'dn.domain_id', '=', 'd.id')
                    ->where('dn.notification_id', $this->notificationId)
                    ->where('dn.status', 'pending')
                    ->select('dn.domain_id', 'd.name as domain_name')
                    ->lockForUpdate()
                    ->get();

                if ($list->isNotEmpty()) {
                    DB::table('domain_notification')
                      ->where('notification_id', $this->notificationId)
                      ->whereIn('domain_id', $list->pluck('domain_id')->all())
                      ->update(['status'=>'queued','sent_at'=>null]);
                }

                return $list;
            });

            if ($pending->isEmpty()) {
                return;
            }

            foreach ($pending as $domain) {
                SendSegmentNotificationDomainJob::dispatch(
                    $this->notificationId,
                    $this->segmentId,
                    $domain->domain_id,
                    $domain->domain_name,
                    $webPush
                );
            }
        } catch (Throwable $e) {
            Log::error("SendSegmentNotificationJob failed [{$this->notificationId}]: {$e->getMessage()}");
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
