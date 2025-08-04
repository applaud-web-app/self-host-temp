<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PushSubscriptionHead;
use App\Models\PushSubscriptionPayload;
use App\Models\PushSubscriptionMeta;
use App\Models\Domain;
use Illuminate\Support\Facades\File;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\PushConfig;
use Illuminate\Support\Facades\Validator;

class ImportExportController extends Controller
{
    public function importView(Request $request){
        try {
            $request->validate([
                'eq' => 'required|string',
            ]);

            $response = decryptUrl($request->eq);
            $domain   = $response['domain'];

            $importUrl = route('migration.import-data');
            $param = ['domain' => $domain];
            $encryptImportUrl = encryptUrl($importUrl, $param);
            return view('import-export.import',compact('encryptImportUrl','domain'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('error','Invalid Request');
        }
    }

    public function importData(Request $request){
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls',
            'eq' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        try {
            $file = $request->file('file');

            $response = decryptUrl($request->eq);
            $domain = $response['domain'] ?? null;

            if (!$domain) {
                return response()->json(['error' => 'Domain not found in the encrypted URL.'], 400);
            }

            $domainRecord = Domain::where('name', $domain)->first();
            if (!$domainRecord) {
                return response()->json(['error' => 'Domain not found.'], 404);
            }

            $data = (new FastExcel)->import($file);

            if (count($data) > 2000) {
                return response()->json(['error' => 'You can upload a maximum of 2,000 records.'], 400);
            }

            foreach ($data as $row) {
                $this->processImportRow($row, $domain);
            }

            return response()->json(['success' => 'Data imported successfully.']);

        } catch (\Throwable $e) {
            Log::error('Import failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error during import. Please try again.'], 500);
        }
    }

    private function processImportRow($row, $domain){
        $token = $row['token'] ?? null;
        $endpoint = $row['endpoint'] ?? null;
        $auth = $row['auth'] ?? null;
        $p256dh = $row['p256dh'] ?? null;
        $ip = $row['ip'] ?? null;
        $status = $row['status'] ?? null;
        $url = $row['url'] ?? null;
        $device = $row['device'] ?? null;
        $browser = $row['browser'] ?? null;
        $platform = $row['platform'] ?? null;
        $country = $row['country'] ?? null;
        $state = $row['state'] ?? null;
        $city = $row['city'] ?? null;

        // Save Push Subscription Head
        $head = PushSubscriptionHead::firstOrNew(['token' => $token]);
        $head->domain = $domain;
        $head->token = $token;
        $head->save();

        // Save Push Subscription Payload
        PushSubscriptionPayload::updateOrCreate(
            ['head_id' => $head->id],
            [
                'endpoint' => $endpoint,
                'auth'     => $auth,
                'p256dh'   => $p256dh,
            ]
        );

        // Save Push Subscription Meta
        PushSubscriptionMeta::updateOrCreate(
            ['head_id' => $head->id],
            [
                'ip_address' => $ip,
                'status' => $status,
                'subscribed_url' => $url,
                'country' => $country,
                'state' => $state,
                'city' => $city,
                'device' => $device,
                'browser' => $browser,
                'platform' => $platform,
            ]
        );
    }

    public function showExportForm(Request $request){
        try {
            $request->validate([
                'eq' => 'required|string',
            ]);

            $response = decryptUrl($request->eq);
            $domain   = $response['domain'];

            $downloadUrl = route('migration.export-data');
            $param = ['domain' => $domain];
            $encryptDownloadUrl = encryptUrl($downloadUrl, $param);
            return view('import-export.export',compact('encryptDownloadUrl','domain'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('error','Invalid Request');
        }
    }

    public function exportData(Request $request){
        try {

            $request->validate([
                'eq' => 'required|string',
            ]);

            $response = decryptUrl($request->eq);
            $domain = $response['domain'];

            // Check if domain exists
            $domainRecord = Domain::where('name', $domain)->first();
            if (!$domainRecord) {
                return response()->json(['error' => 'Domain not found.'], 404);
            }

            // Define $relativePath before the chunking
            $timestamp = now()->format('Ymd_His');
            $filename = "subscribers_backup_{$domainRecord->name}_{$timestamp}.xlsx";
            $relativePath = "backups/{$filename}";
            $fullPath = storage_path("app/public/{$relativePath}");

            // Ensure the backup folder exists, create it if missing
            $backupDirectory = dirname($fullPath);
            if (!File::exists($backupDirectory)) {
                File::makeDirectory($backupDirectory, 0755, true);
            }

            $config = PushConfig::first();
            $publicKey = $config->vapid_public_key ?? '';
            $privateKey = decrypt($config->vapid_private_key) ?? '';

            // Query subscriptions for the specified domain
            $subscriptions = PushSubscriptionHead::where('parent_origin', $domain)->with([
                'payload', 'meta'
            ])->chunk(500, function ($subscriptions) use ($publicKey, $privateKey, $fullPath) {
                $rows = $subscriptions->map(function ($subscription) use ($publicKey, $privateKey) {
                    return [
                        'token' => $subscription->token,
                        'endpoint' => $subscription->payload->endpoint,
                        'auth' => $subscription->payload->auth,
                        'p256dh' => $subscription->payload->p256dh,
                        'ip' => $subscription->meta->ip_address,
                        'status' => $subscription->status,
                        'url' => $subscription->meta->subscribed_url,
                        'country' => $subscription->meta->country,
                        'state' => $subscription->meta->state,
                        'city' => $subscription->meta->city,
                        'device' => $subscription->meta->device,
                        'browser' => $subscription->meta->browser,
                        'platform' => $subscription->meta->platform,
                        'public_key' => $publicKey,
                        'private_key' => $privateKey,
                    ];
                });
                (new FastExcel($rows))->export($fullPath);
            });
            $downloadUrl = url("storage/{$relativePath}");
            return response()->json(['downloadUrl' => $downloadUrl]);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Export failed. Please try again.'], 500);
        }
    }

}
