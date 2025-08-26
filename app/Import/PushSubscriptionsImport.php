<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Domain;
use App\Models\PushSubscriptionHead;
use App\Models\PushSubscriptionPayload;
use App\Models\PushSubscriptionMeta;

class ProcessFastExcelImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $path;   // storage path like "imports/abc.xlsx"
    public string $domain; // domain name
    public int $maxRows;   // enforce your 2,000 cap

    /**
     * @param string $path relative to storage/app (e.g. "imports/uuid.xlsx")
     * @param string $domain
     * @param int    $maxRows
     */
    public function __construct(string $path, string $domain, int $maxRows = 2000)
    {
        $this->path    = $path;
        $this->domain  = $domain;
        $this->maxRows = $maxRows;

        // optional: put on a specific queue
        $this->onQueue('imports');
    }

    // optional: allow long processing
    public $timeout = 1200; // 20 minutes

    public function handle(): void
    {
        $fullPath = storage_path("app/{$this->path}");

        // Validate domain exists (defensive)
        $domainRecord = Domain::where('name', $this->domain)->firstOrFail();

        $count   = 0;
        $batch   = [];
        $batchSize = 500; // DB batch upsert size

        $flushBatch = function () use (&$batch) {
            if (empty($batch)) return;

            DB::transaction(function () use (&$batch) {
                // Upsert Heads
                $heads = [];
                foreach ($batch as $r) {
                    $heads[] = [
                        'token'      => $r['token'],
                        'domain'     => $r['domain'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                PushSubscriptionHead::upsert($heads, ['token'], ['domain','updated_at']);

                // Resolve head_ids by token
                $tokens = collect($batch)->pluck('token')->unique();
                $headIds = PushSubscriptionHead::whereIn('token', $tokens)->pluck('id', 'token');

                // Upsert Payloads & Metas
                $payloads = [];
                $metas    = [];
                foreach ($batch as $r) {
                    if (!isset($headIds[$r['token']])) continue;
                    $hid = $headIds[$r['token']];
                    $payloads[] = [
                        'head_id'   => $hid,
                        'endpoint'  => $r['endpoint'],
                        'auth'      => $r['auth'],
                        'p256dh'    => $r['p256dh'],
                        'created_at'=> now(),
                        'updated_at'=> now(),
                    ];
                    $metas[] = [
                        'head_id'        => $hid,
                        'ip_address'     => $r['ip'],
                        'status'         => $r['status'],
                        'subscribed_url' => $r['url'],
                        'country'        => $r['country'],
                        'state'          => $r['state'],
                        'city'           => $r['city'],
                        'device'         => $r['device'],
                        'browser'        => $r['browser'],
                        'platform'       => $r['platform'],
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                }

                if (!empty($payloads)) {
                    PushSubscriptionPayload::upsert(
                        $payloads,
                        ['head_id'],
                        ['endpoint','auth','p256dh','updated_at']
                    );
                }
                if (!empty($metas)) {
                    PushSubscriptionMeta::upsert(
                        $metas,
                        ['head_id'],
                        ['ip_address','status','subscribed_url','country','state','city','device','browser','platform','updated_at']
                    );
                }
            });

            // clear batch
            $batch = [];
        };

        // Stream rows with FastExcel — no huge in-memory array
        (new FastExcel)->import($fullPath, function (array $row) use (&$count, &$batch, $batchSize, $flushBatch) {
            // stop at maxRows
            if ($count >= $this->maxRows) {
                return; // ignore extra rows
            }

            // normalize headings
            $norm = fn ($k) => strtolower(trim($k));
            $row  = collect($row)->keyBy(fn ($v, $k) => $norm($k))->all();

            $token = (string)($row['token'] ?? '');
            if ($token === '') {
                return; // skip empty token rows
            }

            $batch[] = [
                'domain'   => $this->domain,
                'token'    => $token,
                'endpoint' => (string)($row['endpoint'] ?? null),
                'auth'     => (string)($row['auth'] ?? null),
                'p256dh'   => (string)($row['p256dh'] ?? null),
                'ip'       => (string)($row['ip'] ?? null),
                'status'   => (string)($row['status'] ?? null),
                'url'      => (string)($row['url'] ?? null),
                'country'  => (string)($row['country'] ?? null),
                'state'    => (string)($row['state'] ?? null),
                'city'     => (string)($row['city'] ?? null),
                'device'   => (string)($row['device'] ?? null),
                'browser'  => (string)($row['browser'] ?? null),
                'platform' => (string)($row['platform'] ?? null),
            ];

            $count++;
            if (count($batch) >= $batchSize) {
                $flushBatch();
            }
        });

        // flush remaining
        $flushBatch();
        Log::info('FastExcel import completed', ['domain' => $this->domain, 'rows' => $count]);
    }
}
