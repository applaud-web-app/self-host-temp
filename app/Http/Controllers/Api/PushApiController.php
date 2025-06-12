<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PushConfig;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Jobs\SubscribePushSubscriptionJob;
use Illuminate\Support\Facades\Cache;

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
          // 1) Validate incoming payload
          $data = $request->validate([
              'token'    => 'required|string',
              'domain'   => 'required|string|exists:domains,name',
              'old_token' => 'nullable|string',
              'endpoint' => 'required|url',
              'auth'     => 'required|string',
              'p256dh'   => 'required|string',
          ]);

          // 2) Enrich with IP & UA
          $data['ip_address'] = $request->header('CF-Connecting-IP') ?? $request->getClientIp();
          $data['user_agent'] = $request->userAgent();

          // 3) Dispatch the job
          SubscribePushSubscriptionJob::dispatch($data);

          // 4) Immediate success response
          return response()->json([
              'status'  => 'success',
              'message' => 'Subscription queued for processing.',
          ], 202);

      } catch (ValidationException $e) {
          return response()->json([
              'status'  => 'error',
              'message' => 'Invalid subscription data.',
              'errors'  => $e->errors(),
          ], 422);

      } catch (\Exception $e) {
          Log::error('Failed to dispatch subscription job', [
              'error'   => $e->getMessage(),
              'payload' => $request->all(),
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
          'token' => 'required|string|exists:push_subscriptions_head,token',
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
  
}
