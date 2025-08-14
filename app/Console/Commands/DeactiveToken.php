<?php

// namespace App\Console\Commands;

// use Illuminate\Console\Command;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Throwable;
// use App\Models\Setting;
// use App\Models\PushSubscriptionHead;

// class DeactiveToken extends Command
// {
//     /**
//      * The name and signature of the console command.
//      *
//      * @var string
//      */
//     protected $signature = 'app:deactive-token';

//     /**
//      * The console command description.
//      *
//      * @var string
//      */
//     protected $description = 'Deactivates tokens after multiple consecutive failed notifications and cleans up old notification data';

//     /**
//      * Execute the console command.
//      */
//     public function handle()
//     {
//         try {
            
//             $setting = Setting::first();
//             if ($setting && $setting->daily_cleanup == 0) {
//                 $this->info('Daily cleanup is disabled. Skipping...');
//                 return;
//             }
            
//             $this->cleanupOldNotificationData();

//             DB::table('notification_sends')
//                 ->select('id','subscription_head_id')
//                 ->where('status', 0)
//                 ->groupBy('subscription_head_id')
//                 ->chunkById(500, function ($tokensToDeactivate) {
//                     foreach ($tokensToDeactivate as $token) {
//                         $lastNotifications = DB::table('notification_sends')
//                             ->where('subscription_head_id', $token->subscription_head_id)
//                             ->orderBy('created_at', 'desc')
//                             ->limit(5)
//                             ->pluck('status');

//                         // If all last 5 notifications failed (status == 0), deactivate the token
//                         if ($lastNotifications->every(fn($status) => $status == 0)) {
//                             try {
//                                 DB::beginTransaction();
                                
//                                 // Delete from notification_sends first
//                                 DB::table('notification_sends')
//                                     ->where('subscription_head_id', $token->subscription_head_id)
//                                     ->delete();
                                    
//                                 // Delete from push_subscriptions_meta
//                                 DB::table('push_subscriptions_meta')
//                                     ->where('head_id', $token->subscription_head_id)
//                                     ->delete();
                                    
//                                 // Delete from push_subscriptions_payload
//                                 DB::table('push_subscriptions_payload')
//                                     ->where('head_id', $token->subscription_head_id)
//                                     ->delete();
                                    
//                                 // Finally delete from push_subscriptions_head
//                                 DB::table('push_subscriptions_head')
//                                     ->where('id', $token->subscription_head_id)
//                                     ->delete();
                                    
//                                 DB::commit();
//                                 Log::info('Permanently deleted token and all related data: ' . $token->subscription_head_id);
//                             } catch (Throwable $e) {
//                                 DB::rollBack();
//                                 Log::error('Error deleting token and related data: ' . $token->subscription_head_id . ' - ' . $e->getMessage());
//                             }
//                         }
//                     }
//                 });

//             Log::info('Token deactivation process completed successfully.');
//         } catch (Throwable $e) {
//             // Log any errors in the overall process to ensure the command doesn't fail
//             Log::error('Error running deactivation command: ' . $e->getMessage());
//         }
//     }

//     private function cleanupOldNotificationData()
//     {
//         try {
//             DB::table('notification_sends')->where('created_at', '<', now()->subDays(30))->delete();
//             Log::info('Successfully cleaned up notification_sends table, removed data older than 30 days.');
//         } catch (Throwable $e) {
//             Log::error('Error cleaning up notification_sends table: ' . $e->getMessage());
//         }
//     }
// }

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Models\Setting;
use Carbon\Carbon;

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
     * Configuration constants
     */
    private const BATCH_SIZE = 500;
    private const CLEANUP_DAYS = 30;
    private const FAILED_THRESHOLD = 5;
    private const DELETE_CHUNK_SIZE = 500;
    private const PROGRESS_INTERVAL = 100;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $this->info('Starting token deactivation process...');

        try {
            // Check if daily cleanup is enabled
            if (!$this->isDailyCleanupEnabled()) {
                $this->info('Daily cleanup is disabled. Skipping...');
                return self::SUCCESS;
            }

            // Step 1: Cleanup old notification data
            $cleanupResult = $this->cleanupOldNotificationData();
            
            // Step 2: Process failed tokens
            $deactivationResult = $this->processFailedTokens();
            
            $executionTime = round(microtime(true) - $startTime, 2);
            
            $this->info("Process completed successfully in {$executionTime} seconds");
            $this->info("Cleanup: {$cleanupResult['deleted']} old records removed");
            $this->info("Deactivation: {$deactivationResult['processed']} tokens processed, {$deactivationResult['deleted']} tokens deactivated");

            Log::info('Token deactivation process completed successfully', [
                'execution_time' => $executionTime,
                'cleanup_deleted' => $cleanupResult['deleted'],
                'tokens_processed' => $deactivationResult['processed'],
                'tokens_deleted' => $deactivationResult['deleted']
            ]);

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error('Error during token deactivation process: ' . $e->getMessage());
            Log::error('Token deactivation process failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return self::FAILURE;
        }
    }

    /**
     * Check if daily cleanup is enabled
     */
    private function isDailyCleanupEnabled(): bool
    {
        try {
            return cache()->remember('daily_cleanup_setting', 3600, function () {
                $setting = Setting::select('daily_cleanup')->first();
                return $setting && $setting->daily_cleanup == 1;
            });
        } catch (Throwable $e) {
            return true;
        }
    }

    /**
     * Cleanup old notification data
     */
    private function cleanupOldNotificationData(): array
    {
        try {
            $cutoffDate = Carbon::now()->subDays(self::CLEANUP_DAYS);
            $totalDeleted = 0;
            
            // Get IDs to delete in chunks to avoid memory issues and long locks
            DB::table('notification_sends')
                ->where('created_at', '<', $cutoffDate)
                ->select('id')
                ->chunkById(self::DELETE_CHUNK_SIZE, function ($records) use (&$totalDeleted) {
                    $ids = $records->pluck('id')->toArray();
                    
                    if (!empty($ids)) {
                        $deleted = DB::table('notification_sends')->whereIn('id', $ids)->delete();
                        $totalDeleted += $deleted;
                        $this->info("Deleted {$deleted} old notification records (total: {$totalDeleted})");
                        // Small delay to prevent overwhelming the database
                        usleep(100000); // 0.1 seconds
                    }
                });
            
            if ($totalDeleted > 0) {
                Log::info('Successfully cleaned up notification_sends table', [
                    'deleted_count' => $totalDeleted,
                    'cutoff_date' => $cutoffDate->toDateTimeString()
                ]);
            }
            
            return ['deleted' => $totalDeleted];
            
        } catch (Throwable $e) {
            Log::error('Error cleaning up notification_sends table', [
                'error' => $e->getMessage(),
                'cleanup_days' => self::CLEANUP_DAYS
            ]);
            throw $e;
        }
    }

    /**
     * Process failed tokens and deactivate them
     */
    private function processFailedTokens(): array
    {
        $processed = 0;
        $deleted = 0;
        
        try {
            // Get subscription IDs with failed notifications
            DB::table('notification_sends')
                ->select('subscription_head_id')
                ->where('status', 0)
                ->groupBy('subscription_head_id')
                ->chunkById(self::BATCH_SIZE, function ($tokens) use (&$processed, &$deleted) {
                    foreach ($tokens as $token) {
                        $processed++;
                        
                        if ($this->shouldDeactivateToken($token->subscription_head_id)) {
                            if ($this->deleteTokenAndRelatedData($token->subscription_head_id)) {
                                $deleted++;
                            }
                        }
                        
                        // Progress indication
                        if ($processed % self::PROGRESS_INTERVAL == 0) {
                            $this->info("Processed {$processed} tokens, deleted {$deleted}");
                        }
                    }
                }, 'subscription_head_id');
            
            return ['processed' => $processed, 'deleted' => $deleted];
            
        } catch (Throwable $e) {
            Log::error('Error processing failed tokens', [
                'error' => $e->getMessage(),
                'processed' => $processed,
                'deleted' => $deleted
            ]);
            throw $e;
        }
    }

    /**
     * Check if a token should be deactivated based on consecutive failures
     */
    private function shouldDeactivateToken(int $subscriptionHeadId): bool
    {
        try {
            $lastNotifications = DB::table('notification_sends')
                ->where('subscription_head_id', $subscriptionHeadId)
                ->orderBy('created_at', 'desc')
                ->limit(self::FAILED_THRESHOLD)
                ->pluck('status');

            // Check if we have enough notifications and all are failures
            return $lastNotifications->count() >= self::FAILED_THRESHOLD && 
                   $lastNotifications->every(fn($status) => $status == 0);
                   
        } catch (Throwable $e) {
            Log::warning('Error checking token failure status', [
                'subscription_head_id' => $subscriptionHeadId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete token and all related data with proper transaction handling
     */
    private function deleteTokenAndRelatedData(int $subscriptionHeadId): bool
    {
        try {
            return DB::transaction(function () use ($subscriptionHeadId) {
                // Delete in proper order to maintain referential integrity
                $deletedSends = DB::table('notification_sends')
                    ->where('subscription_head_id', $subscriptionHeadId)
                    ->delete();
                    
                $deletedMeta = DB::table('push_subscriptions_meta')
                    ->where('head_id', $subscriptionHeadId)
                    ->delete();
                    
                $deletedPayload = DB::table('push_subscriptions_payload')
                    ->where('head_id', $subscriptionHeadId)
                    ->delete();
                    
                $deletedHead = DB::table('push_subscriptions_head')
                    ->where('id', $subscriptionHeadId)
                    ->delete();
                
                if ($deletedHead > 0) {
                    Log::info('Successfully deleted token and related data', [
                        'subscription_head_id' => $subscriptionHeadId,
                        'deleted_sends' => $deletedSends,
                        'deleted_meta' => $deletedMeta,
                        'deleted_payload' => $deletedPayload
                    ]);
                    return true;
                }
                
                Log::warning('Token not found for deletion', [
                    'subscription_head_id' => $subscriptionHeadId
                ]);
                return false;
            });
            
        } catch (Throwable $e) {
            Log::error('Error deleting token and related data', [
                'subscription_head_id' => $subscriptionHeadId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }
}