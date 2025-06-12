<?php

namespace App\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;
use App\Models\PushSubscriptionHead;
use App\Models\PushSubscriptionPayload;
use App\Models\PushSubscriptionMeta;
use Illuminate\Support\Facades\Cache;

class SubscribePushSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** @var array */
    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->tries = 3;
        $this->backoff = [60, 300, 600];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function() {
            // 1) HEAD
            $head = PushSubscriptionHead::updateOrCreate(
                ['token'  => $this->data['token']],
                ['domain' => $this->data['domain']]
            );

            // 2) PAYLOAD
            PushSubscriptionPayload::updateOrCreate(
                ['head_id' => $head->id],
                [
                    'endpoint' => $this->data['endpoint'],
                    'auth'     => $this->data['auth'],
                    'p256dh'   => $this->data['p256dh'],
                ]
            );

            // 3) META: lookup geo & device if needed
            $agent = new Agent();
            $agent->setUserAgent($this->data['user_agent'] ?? '');

            $position = null;
            $ip = $this->data['ip_address'];

            $position = Cache::remember("geoip:{$ip}", now()->addHours(6), function() use ($ip) {
                try {
                    return Location::get($ip);
                } catch (\Exception $e) {
                    Log::warning("Location lookup failed for {$ip}: ".$e->getMessage());
                    return null;
                }
            });

            PushSubscriptionMeta::updateOrCreate(
                ['head_id' => $head->id],
                [
                    'ip_address' => $this->data['ip_address'],
                    'country'    => $position->countryName ?? null,
                    'state'      => $position->regionName  ?? null,
                    'city'       => $position->cityName    ?? null,
                    'device'     => $agent->device()       ?? null,
                    'browser'    => $agent->browser()      ?? null,
                    'platform'   => $agent->platform()     ?? null,
                ]
            );
        });
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('SubscribePushSubscriptionJob failed', [
            'error'   => $exception->getMessage(),
            'payload' => $this->data,
        ]);
    }
}
