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


class AddonController extends Controller
{
    public function addons()
    {
        try {
            $installation = Installation::where('licensed_domain', request()->getHost())->latest('created_at')->firstOrFail();
            $licenseKey = $installation->license_key;

            $url = constant('addon-push');
            if (! $url) {
                throw new \Exception('addon-push constant is not defined.');
            }

            // 3) Send the request (no ->throw() so we can inspect errors ourselves)
            $response = Http::timeout(5)
                        ->post($url, [
                            'license_key' => $licenseKey,
                            'domain'      => request()->getHost(),
                        ]);

            // 4) If it failed examine the status & body
            if ($response->failed()) {
                $status = $response->status();
                $body   = $response->json();

                // 5) Special case: invalid license key
                if ($status === 404 && isset($body['error']) && $body['error'] === 'Invalid license key.') {
                    // Installation::truncate();
                    return back();
                }
                return back()->withErrors('Unable to fetch addons. Please try again later.');
            }

            // 7) Success: pull out the addons array
            $data   = $response->json();
            $addons = $data['addons'] ?? [];

            return view('addons.view', compact('addons'));

        } catch (\Illuminate\Http\Client\RequestException $e) {
            return back()->withErrors('Network error while fetching addons. Please try again.');
        } catch (\Throwable $e) {
            return back()->withErrors('Something went wrong.');
        }
    }

    /**
     * Show the form to upload a new add-on ZIP.
     */
    /**
     * Show the upload form.
     */
    public function upload()
    {
        return view('addons.upload');
    }

    /**
     * Handle the uploaded ZIP; extract, cleanup, and record.
     */
    public function store(Request $request)
    {
        $request->validate([
            'zip'     => 'required|file|mimes:zip',
            'version' => 'required|string|max:20',
        ]);

        $file        = $request->file('zip');
        $zipName     = $file->getClientOriginalName();
        $moduleName  = pathinfo($zipName, PATHINFO_FILENAME);
        $modulesDir  = base_path('Modules');
        $zipPath     = "{$modulesDir}/{$zipName}";
        $extractPath = "{$modulesDir}/{$moduleName}";

        if (! is_dir($modulesDir)) {
            mkdir($modulesDir, 0755, true);
        }

        // Replace existing folder
        $wasReplaced = false;
        if (is_dir($extractPath)) {
            File::deleteDirectory($extractPath);
            $wasReplaced = true;
        }

        // Move ZIP and extract
        $file->move($modulesDir, $zipName);
        $zip = new ZipArchive;
        if ($zip->open($zipPath) === true) {
            if (! is_dir($extractPath)) {
                mkdir($extractPath, 0755, true);
            }
            $zip->extractTo($extractPath);
            $zip->close();

            // Delete the ZIP file after successful extraction
            File::delete($zipPath);
        } else {
            Log::error("Failed to extract module ZIP: {$zipPath}");
            return back()->withErrors('Module uploaded but extraction failed.');
        }

        // Database upsert by file_path
        $addon = Addon::updateOrCreate(
            ['file_path' => "Modules/{$zipName}"],
            [
                'name'    => $moduleName,
                'version' => $request->version,
                'status'  => 'installed',
            ]
        );

        // AJAX response
        if ($request->ajax()) {
            return response()->json([
                'replaced'     => $wasReplaced,
                'install_path' => "Modules/{$moduleName}",
            ]);
        }

        // Fallback redirect
        $msg = $wasReplaced
            ? "Module '{$moduleName}' replaced at Modules/{$moduleName}."
            : "Module '{$moduleName}' installed at Modules/{$moduleName}.";
        return redirect()
            ->route('addons.upload')
            ->with('success', $msg);
    }
}
