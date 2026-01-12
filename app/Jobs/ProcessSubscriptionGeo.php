<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;

class ProcessSubscriptionGeo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries = 2;

    public function __construct(private array $records) {}

    public function handle(): void
    {
        foreach ($this->records as $record) {
            $ip = $record->ip_address ?? null;

            // Default values
            $country = 'Unknown';
            $state   = 'Unknown';
            $city    = 'Unknown';

            // Try Geo only for valid IPv4
            if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                try {
                    $geo = Cache::remember(
                        "geoip:{$ip}",
                        now()->addHours(12),
                        fn () => Location::get($ip)
                    );

                    if ($geo) {
                        $country = $geo->countryName ?? 'Unknown';
                        $state   = $geo->regionName ?? 'Unknown';
                        $city    = $geo->cityName ?? 'Unknown';
                    }
                } catch (\Throwable $e) {
                    Log::warning('Geo lookup failed', [
                        'ip' => $ip,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Persist result (success OR Unknown)
            DB::table('push_subscriptions_meta')
            ->where('head_id', $record->head_id)
            ->whereNull('country') 
            ->update([
                'country' => $country,
                'state'   => $state,
                'city'    => $city,
            ]);
        }
    }
}