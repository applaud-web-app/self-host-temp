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
            $installation = Installation::where('licensed_domain', request()->getHost())
                                       ->latest('created_at')
                                       ->firstOrFail();
            $licenseKey = $installation->license_key;

            // Reconstruct obfuscated constant name for Add-ons API
            $key = implode('', [
                base64_decode('dmVu'), 
                chr(100),              
                'or-',                  
                base64_decode('YXBp'), 
                '-addon',
                base64_decode('LWxpc3Q='), 
            ]);
            $apiUrl = constant($key);

            // Call addons API
            $response = Http::timeout(5)
                ->post($apiUrl, [
                    'license_key' => $licenseKey,
                    'domain'      => request()->getHost(),
                ])
                ->throw()
                ->json();

            $addons = $response['addons'] ?? [];

            return view('addons.view', ['addons' => $addons]);

        } catch (\Exception $e) {
            Log::error('Addons fetch failed: ' . $e->getMessage(), [
                'domain' => request()->getHost(),
            ]);
            return back()->withErrors('Unable to fetch addons.');
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
