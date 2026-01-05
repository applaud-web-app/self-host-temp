<?php

namespace App\Console\Commands;

use App\Services\FirebaseTopicService;
use App\Models\PushSubscriptionHead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PushResyncTopic extends Command
{
    protected $signature = 'push:resync-topic {domain : Domain name (example.com)} {--limit=500 : Batch size per run}';
    protected $description = 'Re-subscribe existing tokens to Firebase topic for a domain';

    public function handle(FirebaseTopicService $firebase): int
    {
        $domain = strtolower(trim($this->argument('domain')));
        $limit  = (int) $this->option('limit');

        $this->info("Starting topic resync for domain: {$domain}");

        $query = PushSubscriptionHead::query()
            ->where('domain', $domain)
            ->where('status', 1)
            ->whereNotNull('token');

        $total = $query->count();

        if ($total === 0) {
            $this->warn("No active tokens found for domain: {$domain}");
            return self::SUCCESS;
        }

        $this->info("Total active tokens found: {$total}");

        $query->orderBy('id')->chunkById(1000, function ($subs) use ($firebase, $domain) {
            $tokens = $subs->pluck('token')->filter()->values()->all();
            try {
                $firebase->subscribeBatch($tokens, $domain);
                Log::info('1000 token batch completed');
            } catch (\Throwable $e) {
                Log::warning('Batch topic resync failed', [
                    'domain' => $domain,
                    'count'  => count($tokens),
                    'error'  => $e->getMessage(),
                ]);
            }
        });

        $this->info("Topic resync completed for domain: {$domain}");

        return self::SUCCESS;
    }
}