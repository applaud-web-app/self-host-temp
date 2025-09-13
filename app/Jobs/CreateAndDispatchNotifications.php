<?php
// IS ACTIVE JOB

namespace App\Jobs;

use App\Models\Domain;
use App\Models\Notification;
use App\Models\Segment;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Str;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendNotificationJob;
use App\Jobs\SendSegmentNotificationJob;

class CreateAndDispatchNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;
    public $ids;
    public $segment_type;

    /**
     * Create a new job instance.
     *
     * @param  array  $data
     * @param  array  $ids
     * @param  string $segment_type
     * @return void
     */
    public function __construct($data, $ids, $segment_type)
    {
        $this->data = $data;
        $this->ids = $ids;
        $this->segment_type = $segment_type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Prepare notifications to be inserted in bulk
            $notifications = [];
            foreach ($this->ids as $value) {
                $notifications[] = [
                    'target_url'        => $this->data['target_url'],
                    'domain_id'         => $value,
                    'campaign_name'     => 'CAMP#'.random_int(1000, 9999),
                    'title'             => $this->data['title'],
                    'description'       => $this->data['description'],
                    'banner_image'      => $this->data['banner_image'] ?? null,
                    'banner_icon'       => $this->data['banner_icon'] ?? null,
                    'schedule_type'     => strtolower($this->data['schedule_type']),
                    'one_time_datetime' => $this->data['schedule_type'] === 'Schedule' ? $this->data['one_time_datetime'] : null,
                    'message_id'        => Str::uuid(),
                    'btn_1_title'       => $this->data['btn_1_title'] ?? null,
                    'btn_1_url'         => $this->data['btn_1_url'] ?? null,
                    'btn_title_2'       => $this->data['btn_title_2'] ?? null,
                    'btn_url_2'         => $this->data['btn_url_2'] ?? null,
                    'segment_type'      => $this->data['segment_type'],
                    'segment_id'        => $this->data['segment_id'] ?? null,
                    'status'            => 'pending',
                    'sent_at'           => null,
                    'created_at'        => now(),
                    'updated_at'       => now(),
                ];
            }

            // Bulk insert notifications
            Notification::insert($notifications);

            // Handle job dispatching for instant notifications
            foreach ($this->ids as $value) {
                $notification = Notification::where('domain_id', $value)
                    ->where('status', 'pending')
                    ->latest('created_at')
                    ->first();

                // Instant: fire off immediately
                if ($notification->schedule_type === 'instant') {
                    if ($this->segment_type === 'all' || $this->segment_type === 'api' || $this->segment_type === 'rss') {
                        Log::info("Dispatching instant notification for ID: {$notification->id}");
                        dispatch(new SendNotificationJob($notification->id))->onQueue('notifications');
                    } else {
                        Log::info("Dispatching instant notification for ID: {$notification->id}");
                        dispatch(new SendSegmentNotificationJob($notification->id))->onQueue('notifications');
                    }
                }
            }
        } catch (\Throwable $e) {
            // Log errors if any
            Log::error("Failed to create and dispatch notifications: {$e->getMessage()}", [
                'data' => $this->data,
                'ids' => $this->ids,
            ]);
        }
    }
}