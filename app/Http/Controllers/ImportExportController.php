<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PushSubscriptionHead;
use App\Models\PushSubscriptionPayload;
use App\Models\PushSubscriptionMeta;
use App\Models\Domain;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\PushConfig;
use App\Jobs\ProcessFastExcelImport;
use App\Services\SubscribersImporter;
use Illuminate\Support\Facades\DB;

class ImportExportController extends Controller
{
   public function importView(Request $request)
    {
        $request->validate(['eq' => 'required|string']);

        $response = decryptUrl($request->eq);
        $domain   = $response['domain'];

        $importUrl = route('migration.import-data');
        $encryptImportUrl = encryptUrl($importUrl, ['domain' => $domain]);

        return view('import-export.import', compact('encryptImportUrl', 'domain'));
    }

    public function importData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls|max:102400', // 100 MB
            'eq'   => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $eq = $request->input('eq', $request->query('eq'));
            $response = decryptUrl($eq);
            $domain = $response['domain'] ?? null;

            $domainRecord = Domain::where('name', $domain)->first();
            if (!$domainRecord) {
                return response()->json(['error' => 'Domain not found.'], 404);
            }

            $path = $request->file('file')->store('imports');
            ProcessFastExcelImport::dispatch($path, $domain)->onQueue('imports');

            return response()->json([
                'queued'  => true,
                'message' => 'Import has been queued and will be processed shortly.',
            ], 202);

        } catch (\Throwable $e) {

            Log::error('Import failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error during import. Please try again.'], 500);

        }
    }
    
    public function showExportForm(Request $request)
    {
        try {
            $request->validate(['eq' => 'required|string']);

            $response = decryptUrl($request->eq);
            $domain   = $response['domain'];

            $downloadUrl = route('migration.export-data');
            $encryptDownloadUrl = encryptUrl($downloadUrl, ['domain' => $domain]);

            return view('import-export.export', compact('encryptDownloadUrl', 'domain'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Invalid Request');
        }
    }

    // public function exportData(Request $request)
    // {
    //     try {
    //         $request->validate(['eq' => 'required|string']);

    //         $response = decryptUrl($request->eq);
    //         $domain = $response['domain'];

    //         $domainRecord = Domain::where('name', $domain)->first();
    //         if (!$domainRecord) {
    //             return response()->json(['error' => 'Domain not found.']);
    //         }

    //         $timestamp    = now()->format('Ymd_His');
    //         $filename     = "subscribers_backup_{$domainRecord->name}_{$timestamp}.xlsx";
    //         $relativePath = "backups/{$filename}";
    //         $fullPath     = storage_path("app/public/{$relativePath}");

    //         $backupDirectory = dirname($fullPath);
    //         if (!File::exists($backupDirectory)) {
    //             File::makeDirectory($backupDirectory, 0755, true);
    //         }

    //         $config     = PushConfig::first();
    //         $publicKey  = $config->vapid_public_key ?? '';
    //         // SECURITY: do not export private key
    //         // $privateKey = $config && $config->vapid_private_key ? decrypt($config->vapid_private_key) : '';

    //         $subscriptions = PushSubscriptionHead::where('parent_origin', $domain)
    //             ->with(['payload', 'meta'])
    //             ->get();

    //         if ($subscriptions->isEmpty()) {
    //             return response()->json(['error' => 'No data found to export.']);
    //         }

    //         $rows = $subscriptions->map(function ($subscription) use ($publicKey) {
    //             return [
    //                 'token'     => $subscription->token,
    //                 'endpoint'  => $subscription->payload?->endpoint,
    //                 'auth'      => $subscription->payload?->auth,
    //                 'p256dh'    => $subscription->payload?->p256dh,
    //                 'ip'        => $subscription->meta?->ip_address,
    //                 'status'    => $subscription->meta?->status,
    //                 'url'       => $subscription->meta?->subscribed_url,
    //                 'country'   => $subscription->meta?->country,
    //                 'state'     => $subscription->meta?->state,
    //                 'city'      => $subscription->meta?->city,
    //                 'device'    => $subscription->meta?->device,
    //                 'browser'   => $subscription->meta?->browser,
    //                 'platform'  => $subscription->meta?->platform,
    //                 'public_key'=> $publicKey,
    //             ];
    //         });

    //         (new FastExcel($rows))->export($fullPath);

    //         $downloadUrl = url("storage/{$relativePath}");
    //         return response()->json(['downloadUrl' => $downloadUrl]);
    //     } catch (\Throwable $th) {
    //         Log::error('Export failed', ['error' => $th->getMessage()]);
    //         return response()->json(['error' => 'Export failed. Please try again.'], 500);
    //     }
    // }

    public function exportData(Request $request)
    {
        try {
            $request->validate(['eq' => 'required|string']);
            $response = decryptUrl($request->eq);
            $domain   = $response['domain'];

            $domainRecord = Domain::where('name', $domain)->first();
            if (!$domainRecord) {
                return response()->json(['error' => 'Domain not found.']);
            }

            $timestamp    = now()->format('Ymd_His');
            $filename     = "subscribers_backup_{$domainRecord->name}_{$timestamp}.xlsx";
            $relativePath = "backups/{$filename}";
            $fullPath     = storage_path("app/public/{$relativePath}");

            if (!File::exists(dirname($fullPath))) {
                File::makeDirectory(dirname($fullPath), 0755, true);
            }

            $publicKey = optional(PushConfig::first())->vapid_public_key ?? '';

            // Build ONE query (no N+1), ready for cursor()
            $query = DB::table('push_subscriptions_head as h')
                ->leftJoin('push_subscriptions_payload as p', 'p.head_id', '=', 'h.id')
                ->leftJoin('push_subscriptions_meta as m', 'm.head_id', '=', 'h.id')
                ->where('h.parent_origin', $domain)
                ->orderBy('h.id')
                ->select([
                    'h.token',
                    'p.endpoint', 'p.auth', 'p.p256dh',
                    'm.ip_address', 'h.status', 'm.subscribed_url',
                    'm.country', 'm.state', 'm.city',
                    'm.device', 'm.browser', 'm.platform',
                ]);

            // ✅ Cheap existence check that does NOT consume the cursor
            if (!(clone $query)->exists()) {
                return response()->json(['error' => 'No data found to export.']);
            }

            // ✅ Generator: acceptable to FastExcel and fully streamed (low memory)
            $rowsGenerator = (function () use ($query, $publicKey) {
                foreach ($query->cursor() as $r) {
                    yield [
                        'token'      => $r->token,
                        'endpoint'   => $r->endpoint,
                        'auth'       => $r->auth,
                        'p256dh'     => $r->p256dh,
                        'ip'         => $r->ip_address,
                        'status'     => $r->status,
                        'url'        => $r->subscribed_url,
                        'country'    => $r->country,
                        'state'      => $r->state,
                        'city'       => $r->city,
                        'device'     => $r->device,
                        'browser'    => $r->browser,
                        'platform'   => $r->platform,
                        'public_key' => $publicKey,
                    ];
                }
            })();

            // Pass the Generator to FastExcel
            (new FastExcel($rowsGenerator))->export($fullPath);

            return response()->json(['downloadUrl' => url("storage/{$relativePath}")]);
        } catch (\Throwable $th) {
            Log::error('Export failed', ['error' => $th->getMessage()]);
            return response()->json(['error' => 'Export failed. Please try again.'], 500);
        }
    }
}
