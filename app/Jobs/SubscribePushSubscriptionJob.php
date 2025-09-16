<?php
// IS ACTIVE JOB

namespace App\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;
use App\Models\PushSubscriptionHead;
use App\Models\PushSubscriptionPayload;
use App\Models\PushSubscriptionMeta;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class SubscribePushSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3; // safe retry
    public array $backoff = [60, 300, 600]; // retry spacing (1 min, 5 min, 10 min)
    public int $timeout = 30; // fail fast to avoid blocking queue

    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        $newToken = $this->data['token'];
        $domain   = $this->data['domain'];
        $parent_origin   = $this->data['parent_origin'] ?? null;
        $oldToken = $this->data['old_token'] ?? null;

        $filterToken = $oldToken ?: $newToken;
        $head = null;

        try {

            DB::beginTransaction();

            // STEP 1: Head
            // $head = PushSubscriptionHead::firstOrNew(['token' => $filterToken]);
            // $head->token  = $newToken;
            // $head->domain = $domain;
            // $head->save();

            // STEP 1: Check if the newToken exists in the DB, if so, update, otherwise create
            $head = PushSubscriptionHead::where('token', $newToken)->first();
            if (!$head) {
                // If no head exists for newToken, check for the oldToken
                $head = PushSubscriptionHead::where('token', $oldToken)->first();
                if ($head) {
                    $head->token = $newToken;
                    $head->domain = $domain;
                    $head->parent_origin = $parent_origin ?? $domain;
                    $head->save();
                } else {
                    $head = new PushSubscriptionHead();
                    $head->token = $newToken;
                    $head->domain = $domain;
                    $head->parent_origin = $parent_origin ?? $domain;
                    $head->save();
                }
            } else {
                // If newToken exists, simply update the domain
                $head->domain = $domain;
                $head->save();
            }

            // STEP 2: Payload
            PushSubscriptionPayload::updateOrCreate(
                ['head_id' => $head->id],
                [
                    'endpoint' => $this->data['endpoint'],
                    'auth'     => $this->data['auth'],
                    'p256dh'   => $this->data['p256dh'],
                ]
            );

            DB::commit();
        } catch (\Illuminate\Database\QueryException $e){
            DB::rollBack();
            if ($e->getCode() === '23000') {
                Log::warning('Duplicate entry detected', [
                    'token' => $filterToken,
                    'domain' => $domain
                ]);
            } else {
                Log::error('SubscribePushSubscriptionJob DB error', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // STEP 3: Metadata (outside DB transaction)
        if ($head?->id) {
            try {
                $agent = new Agent();
                $agent->setUserAgent($this->data['user_agent'] ?? '');

                $ip = $this->data['ip_address'];
                $position = Cache::remember("geoip:{$ip}", now()->addHours(6), function () use ($ip) {
                    try {
                        return Location::get($ip);
                    } catch (Throwable $e) {
                        Log::warning("GeoIP failed: {$ip} — " . $e->getMessage());
                        return null;
                    }
                });

                $deviceType = match (true) {
                    $agent->isTablet()  => 'tablet',
                    $agent->isMobile()  => 'mobile',
                    method_exists($agent,'isDesktop') && $agent->isDesktop() => 'desktop',
                    default => 'other',
                };

                $rawUrl = $this->data['url'] ?: ($parent_origin ?: "https://{$domain}");
                if (!preg_match('~^https?://~i', $rawUrl)) {
                    $rawUrl = "https://{$rawUrl}";
                }

                $parts = parse_url($rawUrl);

                $scheme = $parts['scheme'] ?? 'https';
                $host   = $parts['host'] ?? $domain;
                $path   = isset($parts['path']) ? rtrim($parts['path'], '/') : '';

                $subscribedUrl = "{$scheme}://{$host}{$path}/";

                PushSubscriptionMeta::updateOrCreate(
                    ['head_id' => $head->id],
                    [
                        'ip_address' => $ip,
                        'country'    => $position->countryName ?? "other",
                        'state'      => $position->regionName ?? "other",
                        'city'       => $position->cityName ?? "other",
                        'device'     => $deviceType,
                        'browser'    => $agent->browser(),
                        'platform'   => $agent->platform(),
                        'subscribed_url' => $subscribedUrl,
                    ]
                );
            } catch (Throwable $e) {
                Log::warning('⚠️ Metadata enrichment failed', [
                    'message' => $e->getMessage()
                ]);
            }
        }

    }

    public function failed(Throwable $e): void
    {
        Log::critical("📛 SubscribePushSubscriptionJob permanently failed", [
            'error'   => $e->getMessage()
        ]);
    }
}
