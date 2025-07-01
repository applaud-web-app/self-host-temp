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

    public function index()
    {
        return view('update.index', [
            'currentVersion' => $this->currentVersion(),
            'zipReady'       => File::exists("{$this->updateDir}/update.zip"),
            'updateSize'     => File::exists("{$this->updateDir}/update.zip")
                               ? $this->formatBytes(File::size("{$this->updateDir}/update.zip"))
                               : '0 KB',
        ]);
    }

    public function install(Request $request)
    {
        // URL of the new update package (for example, your self-hosted update)
        $updateUrl = 'http://awmtab.in/self-host-server.zip';
        
        // Define path to save the downloaded update
        $zipPath = "{$this->updateDir}/update.zip";
        
        if (File::exists($zipPath)) {
            File::delete($zipPath);  // Delete the previous update if exists
        }

        // Download the update package
        $this->setProgress(10, 'Downloading update...');
        try {
            $this->downloadUpdate($updateUrl, $zipPath);
        } catch (\Throwable $e) {
            Log::error('[Updater] Download failed: '.$e->getMessage());
            return response()->json(['message' => 'Download failed: '.$e->getMessage()], 500);
        }

        // Extract the update package
        $this->setProgress(30, 'Extracting update...');
        $this->extractUpdatePackage($zipPath);

        // Final Cleanup
        $this->setProgress(95, 'Cleaning up...');
        File::delete($zipPath);

        $this->setProgress(100, 'Update completed!');
        
        return response()->json(['message' => 'Application updated successfully!']);
    }

    // Helper methods
    private function setProgress(int $p, string $m): void
    {
        File::put($this->progressFile, json_encode(['progress' => $p, 'message' => $m]));
    }

    private function downloadUpdate(string $url, string $destination)
    {
        $client = new Client();
        $response = $client->get($url, ['sink' => $destination]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Failed to download the update package. HTTP Status: " . $response->getStatusCode());
        }

        // Verify if the file was successfully downloaded
        if (!File::exists($destination)) {
            throw new \Exception('Failed to save the update package.');
        }
    }

    private function extractUpdatePackage(string $zipPath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \Exception('Cannot open ZIP');
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

    private function currentVersion(): string
    {
        $vf = base_path('version.txt');
        if (File::exists($vf)) return trim(File::get($vf));
        $c = json_decode(File::get(base_path('composer.json')), true);
        return $c['version'] ?? '1.0.0';
    }

    private function formatBytes(int $b, int $p = 2): string
    {
        $u = ['B', 'KB', 'MB', 'GB', 'TB'];
        $b = max($b, 0);
        $pow = floor($b ? log($b, 1024) : 0);
        $pow = min($pow, count($u) - 1);
        return round($b / (1 << ($pow * 10)), $p) . ' ' . $u[$pow];
    }
}
