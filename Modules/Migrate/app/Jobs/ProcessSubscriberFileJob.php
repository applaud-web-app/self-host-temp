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

/**
 * Efficient, logged, and resumable subscriber import job.
 *
 * - Streams XLSX/XLS with FastExcel
 * - Buffers rows and performs batched UPSERTs
 * - Detailed structured logs at each stage
 * - Updates TaskTracker status + progress messages
 */
class ProcessSubscriberFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Number of rows to buffer before a DB write */
    private const CHUNK_SIZE = 1000;

    /** How often to update TaskTracker "progress" message (in chunks) */
    private const PROGRESS_EVERY_N_CHUNKS = 1;

    /** Give large files room to finish; adjust to taste */
    public int $tries   = 1;
    public int $timeout = 1800; // 30 minutes
    public $backoff     = [10, 60, 180];

    public function __construct(
        public int $taskId,
        public int $domainId,
        public string $storageDisk,
        public string $filePath,
        public string $migrateFrom
    ) {
    }

    public function handle(): void
    {
        /** @var TaskTracker $task */
        $task = TaskTracker::findOrFail($this->taskId);

        // Transition to PROCESSING
        $task->update([
            'status'     => TaskTracker::STATUS_PROCESSING,
            'started_at' => Carbon::now(),
            'message'    => 'Reading spreadsheet…',
        ]);

        // Basic environment context
        $queueConnection = config('queue.default');
        $diskConfigured  = $this->storageDisk;

        // File checks (critical in multi-container setups)
        $exists = Storage::disk($diskConfigured)->exists($this->filePath);
        $fullPath = $exists
            ? Storage::disk($diskConfigured)->path($this->filePath)
            : null;

        Log::info('ProcessSubscriberFileJob: starting', [
            'task_id'       => $this->taskId,
            'domain_id'     => $this->domainId,
            'migrate_from'  => $this->migrateFrom,
            'queue'         => $this->queue ?? 'default',
            'connection'    => $queueConnection,
            'disk'          => $diskConfigured,
            'file_path'     => $this->filePath,
            'file_exists'   => $exists,
            'resolved_path' => $fullPath,
        ]);

        if (!$exists || !$fullPath) {
            $this->failWithMessage($task, 'Source file not found or not accessible by the worker.');
            return;
        }

        $rowsInserted = 0;
        $rowsUpdated  = 0;
        $rowsSkipped  = 0;
        $rowsTotal    = 0;
        $chunksFlushed = 0;
        $startedAt    = microtime(true);

        // Pre-flight: verify DB connectivity early
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->failWithMessage($task, 'Database connection failed: ' . $e->getMessage(), $e);
            return;
        }

        try {
            $buffer = [];

            $pushRow = function (array $row) use (&$buffer, &$rowsInserted, &$rowsUpdated, &$rowsSkipped, &$rowsTotal, &$chunksFlushed, $task) {
                // Normalize headers to lowercase for flexible mapping
                $lower = array_change_key_case($row, CASE_LOWER);
                $get   = static fn($k) => $lower[strtolower($k)] ?? null;

                $endpoint    = trim((string) ($get('endpoint')   ?? ''));
                $publicKey   = trim((string) ($get('public_key') ?? $get('p256dh') ?? ''));
                $privateKey  = trim((string) ($get('private_key') ?? ''));
                $auth        = trim((string) ($get('auth')       ?? ''));
                $p256dh      = trim((string) ($get('p256dh')     ?? ''));
                $ip          = trim((string) ($get('ip_address') ?? $get('ip') ?? ''));
                $status      = (string) ($get('status') ?? 1);

                $rowsTotal++;

                // Minimal validity: endpoint present + at least one key
                if ($endpoint === '' || ($publicKey === '' && $p256dh === '')) {
                    $rowsSkipped++;
                    return;
                }

                $buffer[] = [
                    'domain_id'        => $this->domainId,
                    'migrate_from'     => $this->migrateFrom,
                    'endpoint'         => $endpoint,
                    'public_key'       => $publicKey,
                    'private_key'      => $privateKey,
                    'auth'             => $auth,
                    'p256dh'           => $p256dh,
                    'ip_address'       => $ip,
                    'migration_status' => 'pending',
                    'status'           => is_numeric($status) ? (int) $status : 1,
                    'updated_at'       => now(),
                    'created_at'       => now(),
                ];

                if (count($buffer) >= self::CHUNK_SIZE) {
                    $this->flushBuffer($buffer, $rowsInserted, $rowsUpdated);

                    $chunksFlushed++;

                    if ($chunksFlushed % self::PROGRESS_EVERY_N_CHUNKS === 0) {
                        $this->updateProgress($task, $rowsTotal, $rowsInserted, $rowsUpdated, $rowsSkipped);
                    }
                }
            };

            // Stream-read the file: each row passes through $pushRow
            (new FastExcel())->import($fullPath, function (array $row) use ($pushRow) {
                $pushRow($row);
                // No return needed for FastExcel's streaming
            });

            // Flush tail
            if (!empty($buffer)) {
                $this->flushBuffer($buffer, $rowsInserted, $rowsUpdated);
                $chunksFlushed++;
                $this->updateProgress($task, $rowsTotal, $rowsInserted, $rowsUpdated, $rowsSkipped);
            }

            $elapsed = round(microtime(true) - $startedAt, 2);

            $summary = sprintf(
                'Imported successfully. Total: %d, Inserted: %d, Updated: %d, Skipped: %d. Time: %ss',
                $rowsTotal,
                $rowsInserted,
                $rowsUpdated,
                $rowsSkipped,
                $elapsed
            );

            Log::info('ProcessSubscriberFileJob: completed', [
                'task_id'      => $this->taskId,
                'domain_id'    => $this->domainId,
                'migrate_from' => $this->migrateFrom,
                'total_rows'   => $rowsTotal,
                'inserted'     => $rowsInserted,
                'updated'      => $rowsUpdated,
                'skipped'      => $rowsSkipped,
                'elapsed_sec'  => $elapsed,
                'chunks'       => $chunksFlushed,
                'chunk_size'   => self::CHUNK_SIZE,
            ]);

            $task->update([
                'status'       => TaskTracker::STATUS_COMPLETED,
                'message'      => $summary,
                'completed_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            $this->failWithMessage($task, 'Import failed: ' . $e->getMessage(), $e);
            throw $e; // Let the queue decide on retry/backoff
        }
    }

    /**
     * Flush a buffered chunk using batched queries and track inserted/updated counts.
     * Splits current buffer into "new" vs "existing" by probing only endpoints in this chunk.
     */
    private function flushBuffer(array &$buffer, int &$rowsInserted, int &$rowsUpdated): void
    {
        if (empty($buffer)) {
            return;
        }

        $count = count($buffer);
        $endpoints = array_column($buffer, 'endpoint');

        // Gather existing subset (for this domain + source + buffer endpoints)
        $existingEndpoints = MigrateSubs::query()
            ->where('domain_id', $this->domainId)
            ->where('migrate_from', $this->migrateFrom)
            ->whereIn('endpoint', $endpoints)
            ->pluck('endpoint')
            ->all();

        $existingSet = array_fill_keys($existingEndpoints, true);

        $toInsert = [];
        $toUpdate = [];

        foreach ($buffer as $row) {
            if (isset($existingSet[$row['endpoint']])) {
                $toUpdate[] = $row;
            } else {
                $toInsert[] = $row;
            }
        }

        // Inserts are fast when batched
        if (!empty($toInsert)) {
            MigrateSubs::insert($toInsert);
            $rowsInserted += count($toInsert);
        }

        // Upsert updates existing rows atomically
        if (!empty($toUpdate)) {
            DB::table((new MigrateSubs())->getTable())->upsert(
                $toUpdate,
                ['domain_id', 'migrate_from', 'endpoint'],
                ['public_key', 'private_key', 'auth', 'p256dh', 'ip_address', 'migration_status', 'status', 'updated_at']
            );
            $rowsUpdated += count($toUpdate);
        }

        Log::debug('ProcessSubscriberFileJob: chunk flushed', [
            'task_id'      => $this->taskId,
            'domain_id'    => $this->domainId,
            'migrate_from' => $this->migrateFrom,
            'chunk_size'   => $count,
            'inserted'     => count($toInsert),
            'updated'      => count($toUpdate),
        ]);

        // Free memory
        $buffer = [];
    }

    /**
     * Update the TaskTracker message with progress details.
     */
    private function updateProgress(TaskTracker $task, int $total, int $inserted, int $updated, int $skipped): void
    {
        $msg = sprintf(
            'Processing… Total seen: %d | Inserted: %d | Updated: %d | Skipped: %d',
            $total, $inserted, $updated, $skipped
        );

        $task->update(['message' => $msg]);

        Log::info('ProcessSubscriberFileJob: progress', [
            'task_id'   => $this->taskId,
            'total'     => $total,
            'inserted'  => $inserted,
            'updated'   => $updated,
            'skipped'   => $skipped,
        ]);
    }

    /**
     * Common failure handler: log + mark TaskTracker failed.
     */
    private function failWithMessage(TaskTracker $task, string $message, \Throwable $e = null): void
    {
        $context = [
            'task_id'      => $this->taskId,
            'domain_id'    => $this->domainId,
            'migrate_from' => $this->migrateFrom,
            'disk'         => $this->storageDisk,
            'file_path'    => $this->filePath,
        ];

        if ($e) {
            $context['exception'] = get_class($e);
            $context['error']     = $e->getMessage();
            $context['trace']     = $e->getTraceAsString();
        }

        Log::error('ProcessSubscriberFileJob: failed - ' . $message, $context);

        $task->update([
            'status'       => TaskTracker::STATUS_FAILED,
            'message'      => $message,
            'completed_at' => Carbon::now(),
        ]);
    }
}