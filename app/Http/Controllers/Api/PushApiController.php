<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\PushConfig;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Jobs\SubscribePushSubscriptionJob;
use Illuminate\Support\Facades\Cache;
use App\Models\PushAnalytic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use App\Jobs\ProcessAnalyticsBatch;

class PushApiController extends Controller
{
  public function sdk(){
    $config = Cache::remember('push_config', now()->addDay(), function() {
      return PushConfig::firstOrFail();
    });

    return response()->view('api.sdk-js', [
      'cfg'   => $config->web_app_config,
      'vapid' => $config->vapid_public_key,
    ])->header('Content-Type', 'application/javascript')
    ->header('Cache-Control', 'public, max-age=86400');
  }

  // public function subscribe(Request $request): JsonResponse
  // {
  //     try {
  //         // Step 1: Validate request payload
  //         $data = $request->validate([
  //           'token'     => 'required|string',
  //           'domain'    => 'required|string',
  //           'old_token' => 'nullable|string',
  //           'endpoint'  => 'required|url',
  //           'auth'      => 'required|string',
  //           'parent_origin' => 'nullable|string',
  //           'p256dh'    => 'required|string',
  //           'url'       => 'nullable|url',
  //         ]);

  //         // Step 2: Enrich data with IP - User Agent
  //         $data['ip_address'] = $request->header('CF-Connecting-IP') ?? $request->getClientIp();
  //         $data['user_agent'] = $request->userAgent();
  //         $data['timestamp']  = now()->timestamp;

  //         // Step 3: Generate subscription hash
  //         $hash = md5($data['token'] . $data['domain'] . $data['endpoint']);
  //         $data['subscription_hash'] = $hash;

  //         // Step 3: Try pushing to Redis
  //         try {
  //           Redis::rpush('buffer:push_subscriptions', json_encode($data));
  //         } catch (\Throwable $e) {
  //           Log::warning('Redis unavailable, falling back to queue for subscribe()', [
  //             'error' => $e->getMessage()
  //           ]);

  //           // Fallback: push directly to queue
  //           SubscribePushSubscriptionJob::dispatch($data);
  //           try {
  //             Redis::sadd('processed:push_subscriptions', $hash);
  //             Redis::expire('processed:push_subscriptions', 3600); // Keep for 1 day
  //           } catch (\Throwable $inner) {
  //             Log::warning('Failed to record processed analytics in Redis', ['error' => $inner->getMessage(),]);
  //           }
  //         }

  //         // Step 4: Respond quickly
  //         return response()->json([
  //             'status'  => 'success',
  //             'message' => 'Subscription received and processing queued.',
  //         ], 202);

  //     } catch (ValidationException $e) {
  //         return response()->json([
  //             'status'  => 'error',
  //             'message' => 'Invalid subscription data.',
  //             'errors'  => $e->errors(),
  //         ], 422);

  //     } catch (\Exception $e) {
  //         Log::error('Subscription dispatch error', [
  //             'error'   => $e->getMessage()
  //         ]);

  //         return response()->json([
  //             'status'  => 'error',
  //             'message' => 'Server error while queuing subscription.',
  //         ], 500);
  //     }
  // }

  public function subscribe(Request $request): JsonResponse
  {
    try {
        // Step 1: Validate incoming data
        // $data = $request->validate([
        //     'token'         => 'required|string',
        //     'domain'        => 'required|string',
        //     'endpoint'      => 'required|url',
        //     'auth'          => 'required|string',
        //     'p256dh'        => 'required|string',
        //     'old_token'     => 'nullable|string',
        //     'parent_origin' => 'required|string',
        //     'url'           => 'nullable|url',
        // ]);

        $data = $request->validate([
          'token'         => 'required|string|max:512',  // ✅ Prevent abuse
          'domain'        => 'required|string|max:255',
          'endpoint'      => 'required|url|max:2048',
          'auth'          => 'required|string|max:255',
          'p256dh'        => 'required|string|max:255',
          'old_token'     => 'nullable|string|max:512',
          'parent_origin' => 'required|string|max:255',  // ✅ Required (matches schema)
          'url'           => 'nullable|url|max:2048',
        ]);

        // Step 2: Enrich with metadata
        $data['ip_address'] = $request->header('CF-Connecting-IP') ?? $request->getClientIp();
        $data['user_agent'] = $request->userAgent();
        $data['timestamp']  = now()->timestamp;

        // Step 3: Hash based on TOKEN ONLY (token is globally unique in your schema)
        $hash = md5($data['token']);
        $data['subscription_hash'] = $hash;

        // Redis keys
        $bufferKey    = 'buffer:push_subscriptions';
        $queuedKey    = 'queued:push_subscriptions';
        $processedKey = 'processed:push_subscriptions';

        // Step 4: Best-effort deduplication check
        try {
            if (Redis::sismember($processedKey, $hash)) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Subscription already exists.',
                ], 200);
            }

            if (Redis::sismember($queuedKey, $hash)) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Subscription already queued.',
                ], 202);
            }
        } catch (\Throwable $e) {
            Log::warning('Redis deduplication check failed; proceeding anyway', [
                'error' => $e->getMessage()
            ]);
        }

        // Step 5: Buffer subscription (preferred)
        try {
            // Prevent Redis memory exhaustion
            $bufferSize = Redis::llen($bufferKey);
            if ($bufferSize > 50000) {
                Log::warning('Buffer size exceeded limit, using direct dispatch', [
                    'buffer_size' => $bufferSize
                ]);
                throw new \RuntimeException('Buffer full');
            }

            Redis::rpush($bufferKey, json_encode($data, JSON_UNESCAPED_SLASHES));

            // Mark queued + refresh TTL
            Redis::sadd($queuedKey, $hash);
            Redis::expire($queuedKey, 86400);

        } catch (\Throwable $e) {
            // Step 6: Fallback - direct dispatch if Redis unavailable/buffer full
            Log::warning('Redis buffering failed; dispatching job directly', [
                'error' => $e->getMessage(),
                'hash'  => $hash
            ]);

            SubscribePushSubscriptionJob::dispatch($data);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Subscription received and queued for processing.',
        ], 202);

    } catch (ValidationException $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Invalid subscription data.',
            'errors'  => $e->errors(),
        ], 422);

    } catch (\Throwable $e) {
        Log::error('Subscription API error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'status'  => 'error',
            'message' => 'Server error while processing subscription.',
        ], 500);
    }
  }

  public function unsubscribe(Request $request): JsonResponse
  {
      try {
        $request->validate([
          'token' => 'required|string',
        ]);

        $deleted = PushSubscriptionHead::where('token', $request->token)->delete();

        return response()->json([
          'status'       => 'success',
          'unsub_count'  => $deleted,
        ]);
      } catch (\Throwable $th) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Server error while queuing subscription.',
        ], 500);
      }
  }

  public function analytics(Request $request): JsonResponse
  {
      $payload = $request->validate([
          'analytics' => 'required|array|max:50',
          'analytics.*.message_id' => 'required|string|max:255',
          'analytics.*.event' => 'required|in:click,close,received',
          'analytics.*.timestamp' => 'sometimes|integer'
      ]);

      try {
          $batchId   = (string) Str::uuid();
          $batchSize = count($payload['analytics']);

          // Log::info('Analytics batch received', [
          //     'batch_id' => $batchId,
          //     'size'     => $batchSize,
          //     'ip'       => $request->ip(),
          // ]);

          // Store canonical batch in Redis (1h TTL) and process async
          $redisKey = "analytics_batch:{$batchId}";
          Redis::setex($redisKey, 3600, json_encode($payload['analytics']));

          ProcessAnalyticsBatch::dispatch($batchId)
              ->onQueue('analytics')
              ->delay(now()->addSeconds(1));

          return response()->json([
              'success'         => true,
              'batch_id'        => $batchId,
              'processed_count' => $batchSize,
              'message'         => 'Analytics batch queued for processing',
          ]);
      } catch (\Throwable $e) {
          Log::error('Analytics enqueue error', [
              'error'        => $e->getMessage(),
              'payload_size' => count($payload['analytics'] ?? []),
          ]);

          return response()->json([
              'success' => false,
              'message' => 'Failed to process analytics',
          ], 500);
      }
  }

}
