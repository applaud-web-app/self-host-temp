<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\Installation;
use App\Models\Addon;
use ZipArchive;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\RequestException;

class AddonController extends Controller
{
    public function addons()
    {
        try {
            // grab current host via the request() helper
            $host = request()->getHost();

            // find the matching installation record
            $installation = Installation::where('licensed_domain', $host)
                                ->latest('created_at')
                                ->firstOrFail();

            $licenseKey = $installation->license_key;

            // your remote endpoint constant
            $url = constant('addon-push');
            if (! $url) {
                throw new \Exception('addon-push constant is not defined.');
            }

            // fetch remote addons
            $response = Http::timeout(5)
                        ->post($url, [
                            'license_key' => $licenseKey,
                            'domain'      => $host,
                        ]);

            // handle HTTP errors
            if ($response->failed()) {
                $body = $response->json();
                if ($response->status() === 404
                    && isset($body['error'])
                    && $body['error'] === 'Invalid license key.'
                ) {
                    return back();
                }
                return back()->withErrors('Unable to fetch addons. Please try again later.');
            }

            // parse and enrich
            $rawAddons = $response->json()['addons'] ?? [];
            $addons = collect($rawAddons)->map(function($addon) {
                $local = Addon::where('name', $addon['name'])
                              ->where('version', $addon['version'])
                              ->first();

                $addon['is_local']     = (bool) $local;
                $addon['local_status'] = $local->status ?? null;

                return $addon;
            });

            return view('addons.view', compact('addons'));
        }
        catch (RequestException $e) {
            return back()->withErrors('Network error while fetching addons. Please try again.');
        }
        catch (\Throwable $e) {
            return back()->withErrors('Something went wrong.');
        }
    }
    
    public function upload(Request $request)
    {
        try {
            // 1) Validate incoming 'eq' parameter
            $request->validate([
                'eq' => 'required|string',
            ]);

            // 2) Decrypt the payload
            $data = decryptUrl($request->eq);
            $param = [
                'key'     => $data['key'],
                'name'    => $data['name'],
                'version' => $data['version'],
            ];

            // 3) If an add-on with this name+version already exists, bail out
            if (Addon::where('name', $param['name'])
                    ->where('version', $param['version'])
                    ->exists()) {
                return redirect()
                    ->back()
                    ->withErrors(['addon' => "The add-on “{$param['name']}” (v{$param['version']}) is already uploaded."]);
            }

            // 4) Build the encrypted store URL and pass to view
            $data['store'] = encryptUrl(route('addons.store'), $param);

            return view('addons.upload', compact('data'));
        }
        catch (\Throwable $th) {
            // You might want to log $th->getMessage() here
            return redirect()
                ->back()
                ->withErrors(['upload' => 'Invalid or expired upload link.']);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'zip' => 'required|file|mimes:zip',
            'eq'  => 'required|string',
        ]);

        // Decrypt parameters
        $data = decryptUrl($request->eq);
        $param = [
            'key'     => $data['key'],
            'name'    => $data['name'],
            'version' => $data['version'],
        ];

        // File paths
        $file        = $request->file('zip');
        $zipName     = $file->getClientOriginalName();
        $moduleName  = pathinfo($zipName, PATHINFO_FILENAME);
        $modulesDir  = base_path('Modules');
        $zipPath     = "{$modulesDir}/{$zipName}";
        $extractPath = "{$modulesDir}/{$moduleName}";
        $fileSize = $file->getSize();

        // Ensure base Modules directory exists
        if (! is_dir($modulesDir)) {
            mkdir($modulesDir, 0755, true);
        }

        DB::beginTransaction();
        try {
            // 1) Check & delete existing module folder
            $wasReplaced = false;
            if (is_dir($extractPath)) {
                File::deleteDirectory($extractPath);
                $wasReplaced = true;
            }

            // 2) Move the uploaded ZIP into the Modules folder
            $file->move($modulesDir, $zipName);

            // 3) Extract it
            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                throw new \Exception("Failed to open ZIP for extraction.");
            }
            // Create target folder (will exist only if not replaced)
            if (! is_dir($extractPath)) {
                mkdir($extractPath, 0755, true);
            }
            $zip->extractTo($extractPath);
            $zip->close();

            // 4) Write to database
            $addon = Addon::updateOrCreate(
                ['file_path' => "Modules/{$zipName}"],
                [
                    'name'      => $param['name'],
                    'version'   => $param['version'],
                    'status'    => 'uploaded',
                    'file_size' => $fileSize,
                ]
            );

            // 5) All good—commit!
            DB::commit();

            // 6) Clean up the ZIP (we no longer need the archive)
            File::delete($zipPath);

            // 7) Respond
            if ($request->ajax()) {
                return response()->json([
                    'replaced'     => $wasReplaced,
                    'install_path' => "Modules/{$moduleName}",
                ]);
            }

            $msg = $wasReplaced
                ? "Module '{$param['name']}' replaced at Modules/{$moduleName}."
                : "Module '{$param['name']}' uploaded at Modules/{$moduleName}.";

            return redirect()->route('addons.view')->with('success', $msg);
        }
        catch (\Exception $e) {
            // Roll back the DB
            DB::rollBack();

            // Clean up any partial work
            if (file_exists($zipPath)) {
                File::delete($zipPath);
            }
            if (is_dir($extractPath)) {
                File::deleteDirectory($extractPath);
            }

            Log::error("Addon upload failed: {$e->getMessage()}");

            // Return error
            $errorMsg = 'Upload failed: ' . $e->getMessage();
            if ($request->ajax()) {
                return response()->json(['error' => $errorMsg], 500);
            }
            return back()->withErrors($errorMsg);
        }
    }

}
