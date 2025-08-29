<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Services\SubscribersImporter;

class ProcessFastExcelImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries   = 3;

    public function __construct(
        public string $path,
        public string $domain
    ) {}

    public function handle(SubscribersImporter $importer): void
    {
        $fullPath = Storage::path($this->path);
        $count = 0;

        (new FastExcel)->import($fullPath, function (array $row) use ($importer, &$count) {
            $count++;
            $norm = [];
            foreach ($row as $k => $v) {
                $norm[strtolower(trim((string)$k))] = $v;
            }

            try {
                $importer->importRow($norm, $this->domain);
            } catch (\Throwable $e) {
                Log::warning('Import row failed', [
                    'domain' => $this->domain,
                    'row'    => $count,
                    'error'  => $e->getMessage(),
                ]);
            }
        });

        try {
            Storage::delete($this->path);
        } catch (\Throwable $e) {
            Log::warning('Could not delete import file', ['error' => $e->getMessage()]);
        }

        Log::info('Import completed', ['domain' => $this->domain, 'rows' => $count]);
    }
}
