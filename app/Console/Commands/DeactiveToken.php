<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeactiveToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:deactive-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivates tokens after multiple consecutive failed notifications and cleans up old notification data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->cleanupOldNotificationData();

            DB::table('notification_sends')
                ->select('subscription_head_id')
                ->where('status', 0)
                ->groupBy('subscription_head_id')
                ->chunkById(1000, function ($tokensToDeactivate) {
                    foreach ($tokensToDeactivate as $token) {
                        $lastNotifications = DB::table('notification_sends')
                            ->where('subscription_head_id', $token->subscription_head_id)
                            ->orderBy('created_at', 'desc')
                            ->limit(5)
                            ->pluck('status');

                        // If all last 5 notifications failed (status == 0), deactivate the token
                        if ($lastNotifications->every(fn($status) => $status == 0)) {
                            try {
                                // Deactivate the token
                                DB::table('push_subscriptions_head')
                                    ->where('id', $token->subscription_head_id)
                                    ->update(['status' => 0]); 
                                Log::info('Deactivated token: ' . $token->subscription_head_id . ' due to consecutive failures.');
                            } catch (Throwable $e) {
                                Log::error('Error deactivating token: ' . $token->subscription_head_id . ' - ' . $e->getMessage());
                            }
                        }
                    }
                });

            Log::info('Token deactivation process completed successfully.');
        } catch (Throwable $e) {
            // Log any errors in the overall process to ensure the command doesn't fail
            Log::error('Error running deactivation command: ' . $e->getMessage());
        }
    }

    private function cleanupOldNotificationData()
    {
        try {
            DB::table('notification_sends')->where('created_at', '<', now()->subDays(30))->delete();

            Log::info('Successfully cleaned up notification_sends table, removed data older than 30 days.');
        } catch (Throwable $e) {
            Log::error('Error cleaning up notification_sends table: ' . $e->getMessage());
        }
    }
}