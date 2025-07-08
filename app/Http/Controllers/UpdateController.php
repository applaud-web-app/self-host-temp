<?php
// app/Http/Controllers/UpdateController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class UpdateController extends Controller
{
    private string $updateDir;
    private string $progressFile;

    public function __construct()
    {
        $this->updateDir    = storage_path('app/package-updates');
        $this->progressFile = storage_path('app/update_progress.json');

        File::ensureDirectoryExists($this->updateDir);
    }

    // Show the update page with current version and upload button
    public function index()
    {
        return view('update.index', [
            'currentVersion' => $this->currentVersion(),
        ]);
    }

    // Handle the uploaded ZIP file
    public function install(Request $request)
    {
        $request->validate([
            'update_zip' => 'required|file|mimes:zip|max:100000', // Adjust the file size if needed
        ]);

        $zipPath = "{$this->updateDir}/update.zip";

        // If the file already exists, delete it before uploading the new one
        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }

        // Save uploaded file
        $this->setProgress(10, 'Uploading ZIP...');
        $request->file('update_zip')->move($this->updateDir, 'update.zip');

        // Extract the uploaded ZIP file
        $this->setProgress(30, 'Extracting update...');
        $this->extractUpdatePackage($zipPath);

        // Final Cleanup
        $this->setProgress(95, 'Cleaning up...');
        File::delete($zipPath);

        // Completion message
        $this->setProgress(100, 'Update completed!');
        
        return response()->json(['message' => 'Application updated successfully!']);
    }

    // Return progress data for the client-side AJAX
    public function progress()
    {
        if (!File::exists($this->progressFile)) {
            return response()->json(['progress' => 0, 'message' => 'Not started.']);
        }

        return response()->json(json_decode(File::get($this->progressFile), true));
    }

    // Helper methods
    private function setProgress(int $p, string $m): void
    {
        File::put($this->progressFile, json_encode(['progress' => $p, 'message' => $m]));
    }

    private function extractUpdatePackage(string $zipPath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \Exception('Cannot open ZIP file.');
        }

        $extractDir = "{$this->updateDir}/extracted";
        if (File::exists($extractDir)) {
            File::deleteDirectory($extractDir);
        }
        File::makeDirectory($extractDir, 0755, true);
        $zip->extractTo($extractDir);
        $zip->close();

        // Copy extracted files to the relevant directories
        foreach (['app', 'resources', 'routes', 'public'] as $d) {
            if (File::exists("$extractDir/$d")) {
                File::copyDirectory("$extractDir/$d", base_path($d));
            }
        }

        // Clean up extracted files
        File::deleteDirectory($extractDir);
    }

    // Get the current version of the app (from `version.txt` or `composer.json`)
    private function currentVersion(): string
    {
        $vf = base_path('version.txt');
        if (File::exists($vf)) return trim(File::get($vf));
        $c = json_decode(File::get(base_path('composer.json')), true);
        return $c['version'] ?? '1.0.0';
    }
}