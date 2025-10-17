<?php

namespace Modules\Migrate\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Modules\Migrate\Models\MigrateSubs;
use App\Models\PushSubscriptionPayload;
use Modules\Migrate\Models\TaskTracker;

class ValidateMigrateSubscriber implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $domain;
    public $taskTrackerId; // Added TaskTracker ID

    public function __construct($domain, $taskTrackerId)
    {
        $this->domain = $domain;
        $this->taskTrackerId = $taskTrackerId;
    }

    public function handle(): void
    {
        $domainId = $this->domain->id;

        // Get the TaskTracker record by ID
        $task = TaskTracker::find($this->taskTrackerId);

        try {
            // Start the task as processing
            $task->status = TaskTracker::STATUS_PROCESSING;
            $task->message = 'Validation in progress.';
            $task->save();

            // Using DB transactions for better memory management
            DB::beginTransaction();

            MigrateSubs::where('domain_id', $domainId)
                ->where('migration_status', 'pending')
                ->chunkById(1000, function ($subscriptions) use ($task) {
                    foreach ($subscriptions as $subscription) {

                        // Find the matching payload
                        $payload = PushSubscriptionPayload::where('auth', $subscription->auth)
                            ->orWhere('p256dh', $subscription->p256dh)
                            ->first();

                        if ($payload) {
                            $subscription->update(['migration_status' => 'migrated']);
                        }
                    }
                });

            DB::commit();

            // After processing, update task status to completed
            $task->status = TaskTracker::STATUS_COMPLETED;
            $task->message = 'Validation completed successfully.';
            $task->completed_at = now();
            $task->save();
        } catch (\Exception $e) {
            // Rollback transaction in case of error
            DB::rollBack();
            $task->status = TaskTracker::STATUS_FAILED;
            $task->message = 'Error in validating subscribers: ' . $e->getMessage();
            $task->save();
            Log::error('Error in validating subscribers: ' . $e->getMessage());
        }
    }
}
