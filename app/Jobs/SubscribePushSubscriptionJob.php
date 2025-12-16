<?php
// IS ACTIVE JOB

// namespace App\Jobs;

// use Throwable;
// use Illuminate\Bus\Queueable;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Bus\Dispatchable;
// use Illuminate\Queue\SerializesModels;
// use Illuminate\Queue\InteractsWithQueue;
// use Jenssegers\Agent\Agent;
// use Stevebauman\Location\Facades\Location;
// use App\Models\PushSubscriptionHead;
// use App\Models\PushSubscriptionPayload;
// use App\Models\PushSubscriptionMeta;
// use Illuminate\Support\Facades\Cache;
// use Illuminate\Support\Facades\Redis;

// class SubscribePushSubscriptionJob implements ShouldQueue
// {
//     use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

//     public int $tries = 3; // safe retry
//     public array $backoff = [60, 300, 600]; // retry spacing (1 min, 5 min, 10 min)
//     public int $timeout = 30; // fail fast to avoid blocking queue

//     protected array $data;

//     public function __construct(array $data)
//     {
//         $this->data = $data;
//     }

//     public function handle(): void
//     {
//         $newToken = $this->data['token'];
//         $domain   = $this->data['domain'];
//         $parent_origin   = $this->data['parent_origin'] ?? null;
//         $oldToken = $this->data['old_token'] ?? null;

//         $filterToken = $oldToken ?: $newToken;
//         $head = null;

//         try {

//             DB::beginTransaction();

//             // STEP 1: Head
//             // $head = PushSubscriptionHead::firstOrNew(['token' => $filterToken]);
//             // $head->token  = $newToken;
//             // $head->domain = $domain;
//             // $head->save();

//             // STEP 1: Check if the newToken exists in the DB, if so, update, otherwise create
//             $head = PushSubscriptionHead::where('token', $newToken)->first();
//             if (!$head) {
//                 // If no head exists for newToken, check for the oldToken
//                 $head = PushSubscriptionHead::where('token', $oldToken)->first();
//                 if ($head) {
//                     $head->token = $newToken;
//                     $head->domain = $domain;
//                     $head->parent_origin = $parent_origin ?? $domain;
//                     $head->save();
//                 } else {
//                     $head = new PushSubscriptionHead();
//                     $head->token = $newToken;
//                     $head->domain = $domain;
//                     $head->parent_origin = $parent_origin ?? $domain;
//                     $head->save();
//                 }
//             } else {
//                 // If newToken exists, simply update the domain
//                 $head->domain = $domain;
//                 $head->save();
//             }

//             // STEP 2: Payload
//             PushSubscriptionPayload::updateOrCreate(
//                 ['head_id' => $head->id],
//                 [
//                     'endpoint' => $this->data['endpoint'],
//                     'auth'     => $this->data['auth'],
//                     'p256dh'   => $this->data['p256dh'],
//                 ]
//             );

//             DB::commit();
//         } catch (\Illuminate\Database\QueryException $e){
//             DB::rollBack();
//             if ($e->getCode() === '23000') {
//                 Log::warning('Duplicate entry detected', [
//                     'token' => $filterToken,
//                     'domain' => $domain
//                 ]);
//             } else {
//                 Log::error('SubscribePushSubscriptionJob DB error', [
//                     'error' => $e->getMessage()
//                 ]);
//                 throw $e;
//             }
//         }catch (Throwable $e) {
//             DB::rollBack();
//             throw $e;
//         }

//         // STEP 3: Metadata (outside DB transaction)
//         if ($head?->id) {
//             try {
//                 $agent = new Agent();
//                 $agent->setUserAgent($this->data['user_agent'] ?? '');

//                 $ip = $this->data['ip_address'];
//                 $position = Cache::remember("geoip:{$ip}", now()->addHours(6), function () use ($ip) {
//                     try {
//                         return Location::get($ip);
//                     } catch (Throwable $e) {
//                         Log::warning("GeoIP failed: {$ip} — " . $e->getMessage());
//                         return null;
//                     }
//                 });

//                 $deviceType = match (true) {
//                     $agent->isTablet()  => 'tablet',
//                     $agent->isMobile()  => 'mobile',
//                     method_exists($agent,'isDesktop') && $agent->isDesktop() => 'desktop',
//                     default => 'other',
//                 };

//                 $rawUrl = $this->data['url'] ?: ($parent_origin ?: "https://{$domain}");
//                 if (!preg_match('~^https?://~i', $rawUrl)) {
//                     $rawUrl = "https://{$rawUrl}";
//                 }

//                 $parts = parse_url($rawUrl);

//                 $scheme = $parts['scheme'] ?? 'https';
//                 $host   = $parts['host'] ?? $domain;
//                 $path   = isset($parts['path']) ? rtrim($parts['path'], '/') : '';

//                 $subscribedUrl = "{$scheme}://{$host}{$path}/";

//                 PushSubscriptionMeta::updateOrCreate(
//                     ['head_id' => $head->id],
//                     [
//                         'ip_address' => $ip,
//                         'country'    => $position->countryName ?? "other",
//                         'state'      => $position->regionName ?? "other",
//                         'city'       => $position->cityName ?? "other",
//                         'device'     => $deviceType,
//                         'browser'    => $agent->browser(),
//                         'platform'   => $agent->platform(),
//                         'subscribed_url' => $subscribedUrl,
//                     ]
//                 );
//             } catch (Throwable $e) {
//                 Log::warning('⚠️ Metadata enrichment failed', [
//                     'message' => $e->getMessage()
//                 ]);
//             }
//         }

//     }

//     public function failed(Throwable $e): void
//     {
//         Log::critical("📛 SubscribePushSubscriptionJob permanently failed", [
//             'error'   => $e->getMessage()
//         ]);
//     }
// }

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Jenssegers\Agent\Agent;

class SubscribePushSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public array $backoff = [10, 30, 60];

    public function __construct(private array $data) {}

    public function handle(): void
    {
        $hash = $this->data['subscription_hash'] ?? null;

        if (!$hash) {
            Log::warning('SubscribePushSubscriptionJob missing subscription_hash; skipping', [
                'data' => $this->data
            ]);
            return;
        }

        // Best-effort dedupe check
        try {
            if (Redis::sismember('processed:push_subscriptions', $hash)) {
                Log::info('Job skipped - subscription already processed', ['hash' => $hash]);
                return;
            }
        } catch (\Throwable $e) {
            // ignore, DB is the guard
        }

        $deviceInfo = $this->parseUserAgent($this->data['user_agent'] ?? null);

        DB::transaction(function () use ($deviceInfo) {
            // HEAD (token is globally unique)
            DB::table('push_subscriptions_head')->updateOrInsert(
                ['token' => $this->data['token']],
                [
                    'domain'        => $this->data['domain'],
                    'parent_origin' => $this->data['parent_origin'],
                    'status'        => 1,
                    'updated_at'    => now(),
                    'created_at'    => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );

            $headId = DB::table('push_subscriptions_head')
                ->where('token', $this->data['token'])
                ->value('id');

            if (!$headId) {
                throw new \RuntimeException('Failed to retrieve head_id after updateOrInsert');
            }

            // PAYLOAD (1:1 via head_id PK)
            DB::table('push_subscriptions_payload')->updateOrInsert(
                ['head_id' => $headId],
                [
                    'endpoint' => $this->data['endpoint'],
                    'auth'     => $this->data['auth'],
                    'p256dh'   => $this->data['p256dh'],
                ]
            );

            // META (1:1 via head_id PK)
            DB::table('push_subscriptions_meta')->updateOrInsert(
                ['head_id' => $headId],
                [
                    'subscribed_url' => $this->data['url'] ?? null,
                    'ip_address'     => $this->data['ip_address'] ?? null,
                    'country'        => null,
                    'state'          => null,
                    'city'           => null,
                    'device'         => $deviceInfo['device'],
                    'browser'        => $deviceInfo['browser'],
                    'platform'       => $deviceInfo['platform'],
                ]
            );

            // Old token cleanup (token is globally unique; cascade handles payload/meta)
            if (!empty($this->data['old_token']) && $this->data['old_token'] !== $this->data['token']) {
                $oldHeadId = DB::table('push_subscriptions_head')
                    ->where('token', $this->data['old_token'])
                    ->value('id');

                if ($oldHeadId && $oldHeadId !== $headId) {
                    DB::table('push_subscriptions_head')->where('id', $oldHeadId)->delete();

                    Log::info('Old token subscription deleted via cascade', [
                        'old_token'   => substr($this->data['old_token'], 0, 20) . '...',
                        'old_head_id' => $oldHeadId,
                        'new_head_id' => $headId
                    ]);
                }
            }
        });

        // Log::info('Push subscription saved to database (3-table structure)', [
        //     'hash'   => $hash,
        //     'domain' => $this->data['domain'] ?? null,
        //     'token'  => isset($this->data['token']) ? (substr($this->data['token'], 0, 20) . '...') : null,
        // ]);

        // Redis bookkeeping after DB commit (best-effort)
        try {
            Redis::sadd('processed:push_subscriptions', $hash);
            Redis::expire('processed:push_subscriptions', 86400);
            Redis::srem('queued:push_subscriptions', $hash);

            // Log::info('Subscription fully processed (DB + Redis)', ['hash' => $hash]);

        } catch (\Throwable $e) {
            Log::warning('Redis bookkeeping failed after DB save (non-critical)', [
                'hash'  => $hash,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return ['device' => null, 'browser' => null, 'platform' => null];
        }

        try {
            $agent = new Agent();
            $agent->setUserAgent($userAgent);

            return [
                'device'   => $agent->device() ?: ($agent->isDesktop() ? 'Desktop' : 'Unknown'),
                'browser'  => $agent->browser(),
                'platform' => $agent->platform(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Failed to parse user agent', ['error' => $e->getMessage()]);
            return ['device' => null, 'browser' => null, 'platform' => null];
        }
    }

    public function failed(\Throwable $exception): void
    {
        $hash = $this->data['subscription_hash'] ?? null;

        Log::error('SubscribePushSubscriptionJob permanently failed after all retries', [
            'hash'  => $hash,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        if ($hash) {
            try {
                Redis::srem('queued:push_subscriptions', $hash);
            } catch (\Throwable $ignore) {}
        }
    }
}