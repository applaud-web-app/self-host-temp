<?php

namespace Modules\Migrate\Jobs;

use Carbon\Carbon;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Migrate\Models\TaskTracker;
use Modules\Migrate\Models\MigrateSubs;

class ProcessSubscriberFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $taskId,
        public int $domainId,
        public string $storageDisk,
        public string $filePath,
        public string $migrateFrom
    ) {
        $this->onQueue('default'); // adjust queue if you have one
    }

    public function handle(): void
    {
        /** @var TaskTracker $task */
        $task = TaskTracker::findOrFail($this->taskId);

        $task->update([
            'status'     => TaskTracker::STATUS_PROCESSING,
            'started_at' => Carbon::now(),
            'message'    => 'Reading spreadsheet…',
        ]);

        $fullPath = Storage::disk($this->storageDisk)->path($this->filePath);

        $rowsInserted = 0;
        $rowsUpdated  = 0;
        $rowsSkipped  = 0;

        try {
            // FastExcel streams rows (memory-friendly)
            (new FastExcel())->import($fullPath, function (array $row) use (&$rowsInserted, &$rowsUpdated, &$rowsSkipped) {
                // Normalize headers (case-insensitive match)
                $get = function ($key) use ($row) {
                    // try exact, then lowercased, then trimmed keys
                    if (array_key_exists($key, $row)) return $row[$key];
                    $lower = array_change_key_case($row, CASE_LOWER);
                    return $lower[strtolower($key)] ?? null;
                };

                $endpoint    = trim((string) ($get('endpoint')   ?? ''));
                $publicKey   = trim((string) ($get('public_key') ?? $get('p256dh') ?? ''));
                $privateKey  = trim((string) ($get('private_key') ?? ''));
                $auth        = trim((string) ($get('auth')       ?? ''));
                $p256dh      = trim((string) ($get('p256dh')     ?? ''));
                $ip          = trim((string) ($get('ip_address') ?? $get('ip') ?? ''));
                $status      = (string) ($get('status') ?? 1);

                // Basic validation: require endpoint and at least one key
                if ($endpoint === '' || ($publicKey === '' && $p256dh === '')) {
                    $rowsSkipped++;
                    return null;
                }

                // Decide unique constraint for "existing" (often endpoint is unique)
                $existing = MigrateSubs::where('domain_id', $this->domainId)
                    ->where('migrate_from', $this->migrateFrom)
                    ->where('endpoint', $endpoint)
                    ->first();

                if ($existing) {
                    $existing->fill([
                        'public_key'       => $publicKey ?: $existing->public_key,
                        'private_key'      => $privateKey ?: $existing->private_key,
                        'auth'             => $auth ?: $existing->auth,
                        'p256dh'           => $p256dh ?: $existing->p256dh,
                        'ip_address'       => $ip ?: $existing->ip_address,
                        'status'           => is_numeric($status) ? (int)$status : $existing->status,
                    ])->save();

                    $rowsUpdated++;
                } else {
                    MigrateSubs::create([
                        'domain_id'        => $this->domainId,
                        'endpoint'         => $endpoint,
                        'migrate_from'     => $this->migrateFrom,
                        'public_key'       => $publicKey,
                        'private_key'      => $privateKey,
                        'auth'             => $auth,
                        'p256dh'           => $p256dh,
                        'ip_address'       => $ip,
                        'migration_status' => 'pending',
                        'status'           => is_numeric($status) ? (int)$status : 1,
                    ]);
                    $rowsInserted++;
                }

                return null;
            });

            $task->update([
                'status'       => TaskTracker::STATUS_COMPLETED,
                'message'      => "Imported successfully. Inserted: {$rowsInserted}, Updated: {$rowsUpdated}, Skipped: {$rowsSkipped}.",
                'completed_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessSubscriberFileJob failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            $task->update([
                'status'       => TaskTracker::STATUS_FAILED,
                'message'      => 'Import failed: '.$e->getMessage(),
                'completed_at' => Carbon::now(),
            ]);

            // Re-throw if you want the job retried by the queue worker
            throw $e;
        }
    }
}
