<?php
//  app/Http/Controllers/UpdateController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;

class UpdateController extends Controller
{
    private string $updateDir;
    private string $backupDir;
    private string $logFile;
    private string $progressFile;

    public function __construct()
    {
        $this->updateDir    = storage_path('app/package-updates');
        $this->backupDir    = storage_path('app/package-backups');
        $this->logFile      = storage_path('logs/package-update.log');
        $this->progressFile = storage_path('app/update_progress.json');

        File::ensureDirectoryExists($this->updateDir);
        File::ensureDirectoryExists($this->backupDir);
    }

    /* --------------------------------------------------------------------------
     |  UI endpoints
     | ----------------------------------------------------------------------- */
    public function index()
    {
        return view('update.index', [
            'currentVersion' => $this->currentVersion(),
            'zipReady'       => File::exists("{$this->updateDir}/update.zip"),
            'updateSize'     => File::exists("{$this->updateDir}/update.zip")
                                   ? $this->formatBytes(File::size("{$this->updateDir}/update.zip"))
                                   : '0 KB',
            'backups'        => collect($this->getAvailableBackups()),
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'update_file' => 'required|file|mimes:zip|max:102400',
        ]);

        File::cleanDirectory($this->updateDir);

        try {
            $request->file('update_file')->move($this->updateDir, 'update.zip');

            if (! $this->validateUpdatePackage()) {
                File::delete("{$this->updateDir}/update.zip");
                return response()->json(['message' => 'Invalid update package.'], 422);
            }

            return response()->json(['message' => 'Package uploaded – ready to install.']);
        } catch (\Throwable $e) {
            Log::error('Upload failed: '.$e->getMessage());
            return response()->json(['message' => 'Upload failed.'], 500);
        }
    }

    public function install()
    {
        $zipPath = "{$this->updateDir}/update.zip";
        if (! File::exists($zipPath)) {
            return response()->json(['message' => 'No package found.'], 404);
        }

        $this->initProgress();

        try {
            /* 1️⃣ maintenance */
            $this->setProgress(10, 'Entering maintenance mode…');
            Artisan::call('down', ['--retry' => 30]);

            /* 2️⃣ backup */
            $this->setProgress(20, 'Creating backup…');
            $backupPath = $this->createBackup();

            /* 3️⃣ keep a copy of .env */
            $envPath      = base_path('.env');
            $envTemp      = "{$this->updateDir}/env.bak";
            if (File::exists($envPath)) {
                File::copy($envPath, $envTemp);
            }

            /* 4️⃣ extract */
            $this->setProgress(40, 'Extracting update…');
            $this->extractUpdatePackage($zipPath);

            /* 5️⃣ restore .env */
            if (File::exists($envTemp)) {
                File::copy($envTemp, $envPath);
                File::delete($envTemp);
            }

            /* 6️⃣ migrate */
            $this->setProgress(60, 'Running migrations…');
            Artisan::call('migrate', ['--force' => true]);

            /* 7️⃣ optimise */
            $this->setProgress(80, 'Optimising application…');
            Artisan::call('optimize:clear');
            Artisan::call('optimize');

            /* 8️⃣ finish */
            $this->setProgress(90, 'Cleaning up…');
            File::delete($zipPath);
            Artisan::call('up');

            $this->setProgress(100, 'Update completed!');
            $this->logUpdate($backupPath);

            return response()->json(['message' => 'Application updated successfully!']);
        } catch (\Throwable $e) {
            if (isset($backupPath)) {
                $this->restoreFromBackup($backupPath);
            }
            Artisan::call('up');
            Log::error('Update failed: '.$e->getMessage());
            return response()->json(['message' => 'Update failed: '.$e->getMessage()], 500);
        }
    }

    public function progress()
    {
        if (! File::exists($this->progressFile)) {
            return response()->json(['progress' => 0, 'message' => 'No update running']);
        }
        return response()->json(json_decode(File::get($this->progressFile), true));
    }

    public function restore(Request $request)
    {
        $request->validate(['date' => 'required|string']);
        $backupPath = "{$this->backupDir}/{$request->date}";
        if (! File::exists($backupPath)) {
            return response()->json(['message' => 'Backup not found'], 404);
        }

        try {
            Artisan::call('down', ['--retry' => 30]);
            $this->restoreFromBackup($backupPath);
            Artisan::call('up');
            return response()->json(['message' => 'Restored from backup '.$request->date]);
        } catch (\Throwable $e) {
            Artisan::call('up');
            Log::error('Restore failed: '.$e->getMessage());
            return response()->json(['message' => 'Restore failed: '.$e->getMessage()], 500);
        }
    }

    /* --------------------------------------------------------------------------
     |  Helpers
     | ----------------------------------------------------------------------- */
    private function initProgress(): void
    {
        File::put($this->progressFile, json_encode(['progress' => 0, 'message' => 'Starting…']));
    }
    private function setProgress(int $p, string $m): void
    {
        File::put($this->progressFile, json_encode(['progress' => $p, 'message' => $m]));
    }

    private function validateUpdatePackage(): bool
    {
        $zip = new ZipArchive();
        if ($zip->open("{$this->updateDir}/update.zip") !== true) {
            return false;
        }
        $needed = ['composer.json', 'app/', 'public/'];
        foreach ($needed as $path) {
            if ($zip->locateName($path, ZipArchive::FL_NOCASE) === false) {
                $zip->close();  return false;
            }
        }
        $zip->close();
        return true;
    }

    private function createBackup(): string
    {
        $dst = "{$this->backupDir}/".now()->format('Y-m-d_His');
        File::makeDirectory($dst, 0755, true);

        $dirs  = ['app','config','resources','routes','public','database'];
        $files = ['composer.json','composer.lock','.env'];   // keep .env for reference, but we won't overwrite on update

        foreach ($dirs as $d)  if (File::exists(base_path($d)))
            File::copyDirectory(base_path($d), "$dst/$d");
        foreach ($files as $f) if (File::exists(base_path($f)))
            File::copy(base_path($f), "$dst/$f");

        return $dst;
    }

    private function extractUpdatePackage(string $zipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \Exception('Cannot open ZIP');
        }

        // protect against zip-slip
        for ($i=0;$i<$zip->numFiles;$i++) {
            $n = $zip->getNameIndex($i);
            if (Str::startsWith($n, ['/','\\']) ||
                Str::contains($n, ['..'.DIRECTORY_SEPARATOR,'..\\'])
            ) {
                $zip->close();
                throw new \Exception("Unsafe entry: $n");
            }
        }
        // replace key dirs to avoid stale files
        foreach (['app','resources','routes','public'] as $d) {
            File::deleteDirectory(base_path($d));
        }
        if (! $zip->extractTo(base_path())) {
            $zip->close();  throw new \Exception('Extract failed');
        }
        $zip->close();
    }

    private function restoreFromBackup(string $src): void
    {
        $dirs  = ['app','config','resources','routes','public','database'];
        $files = ['composer.json','composer.lock'];  // NOTICE: .env intentionally omitted

        foreach ($dirs as $d) {
            if (File::exists("$src/$d")) {
                File::deleteDirectory(base_path($d));
                File::copyDirectory("$src/$d", base_path($d));
            }
        }
        foreach ($files as $f) {
            if (File::exists("$src/$f")) {
                File::copy("$src/$f", base_path($f));
            }
        }
    }

    private function getAvailableBackups(): array
    {
        if (! File::exists($this->backupDir)) return [];
        return collect(File::directories($this->backupDir))
            ->map(fn($d)=>['date'=>basename($d),'size'=>$this->formatBytes($this->dirSize($d))])
            ->sortByDesc('date')->values()->all();
    }

    private function dirSize(string $p): int
    {
        return collect(File::allFiles($p))->sum(fn($f)=>$f->getSize());
    }

    private function formatBytes(int $b,int $p=2): string
    {
        $u=['B','KB','MB','GB','TB'];
        $b=max($b,0); $pow=floor($b?log($b,1024):0);
        $pow=min($pow,count($u)-1);
        return round($b/(1<<($pow*10)),$p).' '.$u[$pow];
    }

    private function currentVersion(): string
    {
        $verFile = base_path('version.txt');
        if (File::exists($verFile)) return trim(File::get($verFile));
        $c = json_decode(File::get(base_path('composer.json')), true);
        return $c['version'] ?? '1.0.0';
    }

    private function logUpdate(string $backup): void
    {
        File::append($this->logFile, '['.now()."] Update done • backup $backup\n");
    }
}
