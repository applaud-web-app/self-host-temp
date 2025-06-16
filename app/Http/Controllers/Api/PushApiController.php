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
use App\Jobs\ProcessClickAnalytics;
use Illuminate\Support\Facades\Redis;

class PushApiController extends Controller
{
  public function sdk(){
    $config = Cache::remember('push_config', now()->addDay(), function() {
      return PushConfig::firstOrFail();
    });

    return response()->view('api.sdk-js', [
      'cfg'   => $config->web_app_config,
      'vapid' => $config->vapid_public_key,
    ])->header('Content-Type', 'application/javascript');
  }

  public function subscribe(Request $request): JsonResponse
  {
      try {
          // Step 1: Validate request payload
          $data = $request->validate([
              'token'     => 'required|string',
              'domain'    => 'required|string',
              'old_token' => 'nullable|string',
              'endpoint'  => 'required|url',
              'auth'      => 'required|string',
              'p256dh'    => 'required|string',
          ]);

          // Step 2: Enrich data with IP & User Agent
          $data['ip_address'] = $request->header('CF-Connecting-IP') ?? $request->getClientIp();
          $data['user_agent'] = $request->userAgent();
          $data['timestamp']  = now()->timestamp;

          // Step 3: Generate subscription hash
          $hash = md5($data['token'] . $data['domain'] . $data['endpoint']);
          $data['subscription_hash'] = $hash;

          // Step 3: Try pushing to Redis
          try {
            Redis::rpush('buffer:push_subscriptions', json_encode($data));
          } catch (\Throwable $e) {
            Log::warning('Redis unavailable, falling back to queue for subscribe()', [
              'error' => $e->getMessage()
            ]);

            // Fallback: push directly to queue
            SubscribePushSubscriptionJob::dispatch($data);
            try {
              Redis::sadd('processed:push_subscriptions', $hash);
              Redis::expire('processed:push_subscriptions', 3600); // Keep for 1 day
            } catch (\Throwable $inner) {
              Log::warning('Failed to record processed analytics in Redis', ['error' => $inner->getMessage(),]);
            }
          }

          // Step 4: Respond quickly
          return response()->json([
              'status'  => 'success',
              'message' => 'Subscription received and processing queued.',
          ], 202);

      } catch (ValidationException $e) {
          return response()->json([
              'status'  => 'error',
              'message' => 'Invalid subscription data.',
              'errors'  => $e->errors(),
          ], 422);

      } catch (\Exception $e) {
          Log::error('Subscription dispatch error', [
              'error'   => $e->getMessage()
          ]);

          return response()->json([
              'status'  => 'error',
              'message' => 'Server error while queuing subscription.',
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
      'message_id' => 'required|string',
      'event'      => 'required|in:click,close,received',
      'domain'    => 'required|string',
    ]);

    // Add timestamp if needed for future trace/debug
    $event = [
      'message_id' => $payload['message_id'],
      'event'      => $payload['event'],
      'domain'     => $payload['domain'],   
      'timestamp'  => now()->timestamp,
    ];

    // Push to Redis buffer
    try {
      Redis::rpush('buffer:push_events', json_encode($event));
    } catch (\Throwable $e) {
      Log::warning('Redis unavailable, falling back to queue', [
        'error' => $e->getMessage()
      ]);

      // ✅ Fallback to queue
      ProcessClickAnalytics::dispatch($event['message_id'], $event['event'], $event['domain']);

      // ✅ Record this event hash so flush won't double-count later
      $hash = "{$event['event']}|{$event['message_id']}|{$event['domain']}";
      try {
        Redis::sadd('processed:push_analytics', $hash);
        Redis::expire('processed:push_analytics', 3600);
      } catch (\Throwable $inner) {
        Log::warning('Failed to record processed analytics in Redis', ['error' => $inner->getMessage(),]);
      }
    }

    return response()->json(['status' => 'success']);

  }
  
}
