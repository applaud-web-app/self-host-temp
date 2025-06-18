<?php

// App/Jobs/SendSegmentNotificationJob.php

namespace App\Jobs;

use App\Models\Notification;
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

            $n = Notification::with(['domains','segment.deviceRules','segment.geoRules'])->find($this->notificationId);
            if (! $n) {
                Log::error("Notification {$this->notificationId} not found");
                return;
            }

            // build payload inline 
            $webPush = $this->buildWebPush($n);

            foreach ($n->domains as $domain) {
                SendSegmentNotificationDomainJob::dispatch(
                    $this->notificationId,
                    $this->segmentId,
                    $domain->id,
                    $domain->name,
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
