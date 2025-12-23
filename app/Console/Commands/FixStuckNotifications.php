<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FixStuckNotifications extends Command
{
    protected $signature = 'notifications:fix-stuck {--dry-run : Preview without making changes}';
    protected $description = 'Fix notifications stuck in processing/queued state';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔍 Scanning for stuck notifications...');

        // Find notifications stuck in queued/processing for > 10 minutes
        $stuck = DB::table('notifications')
            ->whereIn('status', ['queued', 'processing'])
            ->where('updated_at', '<', Carbon::now()->subMinutes(10))
            ->get(['id', 'status', 'active_count', 'success_count', 'failed_count', 'chunks_total', 'chunks_done', 'updated_at']);

        if ($stuck->isEmpty()) {
            $this->info('✅ No stuck notifications found');
            return 0;
        }

        $this->warn("Found {$stuck->count()} stuck notifications:");

        foreach ($stuck as $n) {
            $this->line("ID: {$n->id} | Status: {$n->status} | Chunks: {$n->chunks_done}/{$n->chunks_total} | Updated: {$n->updated_at}");

            if ($dryRun) {
                continue;
            }

            // Fix based on chunk progress
            if ($n->chunks_total > 0 && $n->chunks_done >= $n->chunks_total) {
                // All chunks done but not marked as sent
                DB::table('notifications')->where('id', $n->id)->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info("  ✅ Marked as SENT (all chunks completed)");
                
            } else if ($n->chunks_total > 0 && $n->chunks_done > 0) {
                // Partial completion - mark as failed
                DB::table('notifications')->where('id', $n->id)->update([
                    'status' => 'failed',
                    'updated_at' => now(),
                ]);
                $this->warn("  ⚠️  Marked as FAILED (partial completion)");
                
            } else {
                // No chunks processed - mark as failed
                DB::table('notifications')->where('id', $n->id)->update([
                    'status' => 'failed',
                    'sent_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->error("  ❌ Marked as FAILED (no progress)");
            }
        }

        if ($dryRun) {
            $this->warn('🔍 Dry run completed - no changes made. Run without --dry-run to fix.');
        } else {
            $this->info('✅ Fixed all stuck notifications');
        }

        return 0;
    }
}