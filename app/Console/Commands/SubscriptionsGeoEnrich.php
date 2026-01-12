<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessSubscriptionGeo;

class SubscriptionsGeoEnrich extends Command
{
    protected $signature = 'subscriptions:geo-enrich';
    protected $description = 'Dispatch jobs to enrich missing GeoIP data';

    public function handle(): int
    {
        $limit = 50; // SAFE for 2-core CPU

        $records = DB::table('push_subscriptions_meta')
            ->whereNull('country')
            ->limit($limit)
            ->get(['head_id', 'ip_address']);

        if ($records->isEmpty()) {
            $this->info('No records require geo enrichment');
            return Command::SUCCESS;
        }

        ProcessSubscriptionGeo::dispatch($records->toArray());

        $this->info("Dispatched geo enrichment job for {$records->count()} records");
        return Command::SUCCESS;
    }
}