<?php
// app/Http/Controllers/UpdateController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
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
        $request->validate(['update_file' => 'required|file|mimes:zip|max:102400']);
        File::cleanDirectory($this->updateDir);

        try {
            $request->file('update_file')->move($this->updateDir, 'update.zip');
            if (! $this->validateUpdatePackage()) {
                File::delete("{$this->updateDir}/update.zip");
                return response()->json(['message' => 'Invalid update package.'], 422);
            }
            return response()->json(['message' => 'Package uploaded – ready to install.']);
        } catch (\Throwable $e) {
            Log::error('[Updater] Upload failed: '.$e->getMessage());
            return response()->json(['message' => 'Upload failed: '.$e->getMessage()], 500);
        }
    }

    public function install()
    {
        $zipPath = "{$this->updateDir}/update.zip";
        if (! File::exists($zipPath)) {
            return response()->json(['message' => 'No package found.'], 404);
        }

        $this->initProgress();
        $backupPath = null;

        try {
            // 1. Backup
            $this->setProgress(10, 'Creating backup…');
            $backupPath = $this->createBackup();

            // 2. Preserve .env
            $this->setProgress(20, 'Preserving environment…');
            $envPath = base_path('.env');
            $envTemp = "{$this->updateDir}/env.bak";
            if (File::exists($envPath)) {
                File::copy($envPath, $envTemp);
            }

            // 3. Extract update
            $this->setProgress(30, 'Extracting update…');
            $this->extractUpdatePackage($zipPath);

            // 4. Restore .env
            $this->setProgress(40, 'Restoring environment…');
            if (File::exists($envTemp)) {
                File::copy($envTemp, $envPath);
                File::delete($envTemp);
            }

            // 5. (Skip composer if vendor exists)
            $this->setProgress(45, 'Dependencies ready.');

            // 6. Run migrations with real-time feedback
            $this->setProgress(60, 'Running migrations…');
            $process = new Process([PHP_BINARY, base_path('artisan'), 'migrate', '--force', '--no-interaction'], base_path());
            $process->setTimeout(3600);
            $process->run(function ($type, $buffer) {
                $this->setProgress(60, trim($buffer));
            });
            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            $this->setProgress(70, 'Migrations completed.');

            // 7. Re-cache config & routes
            $this->setProgress(75, 'Re-caching config & routes…');
            $commands = [
                ['config:clear'], ['config:cache'],
                ['route:clear'], ['route:cache'],
            ];
            foreach ($commands as $cmd) {
                $p = new Process(array_merge([PHP_BINARY, base_path('artisan')], $cmd), base_path());
                $p->setTimeout(300);
                $p->run();
                if (! $p->isSuccessful()) {
                    throw new ProcessFailedException($p);
                }
            }
            $this->setProgress(80, 'Config & routes re-cached.');

            // 8. Optimize and clear cache
            $this->setProgress(85, 'Clearing opcache & optimizing…');
            if (function_exists('opcache_reset')) opcache_reset();
            Artisan::call('optimize:clear');
            Artisan::call('optimize');

            // 9. Cleanup
            $this->setProgress(95, 'Cleaning up…');
            File::delete($zipPath);

            $this->setProgress(100, 'Update completed!');
            $this->logUpdate($backupPath);

            return response()->json(['message' => 'Application updated successfully!']);
        } catch (ProcessFailedException $e) {
            Log::error('[Updater] Process failed: '.$e->getMessage());
            return response()->json(['message' => trim($e->getProcess()->getErrorOutput())], 500);
        } catch (\Throwable $e) {
            if ($backupPath) $this->restoreFromBackup($backupPath);
            Log::error('[Updater] Update failed: '.$e->getMessage());
            return response()->json(['message' => 'Update failed: '.$e->getMessage()], 500);
        }
    }

    public function progress()
    {
        if (! File::exists($this->progressFile)) {
            return response()->json(['progress'=>0,'message'=>'No update running']);
        }
        return response()->json(json_decode(File::get($this->progressFile), true));
    }
        

















    
    public function restore(Request $request)
    {
        $request->validate(['date'=>'required|string']);
        $backupPath = "{$this->backupDir}/{$request->date}";
        if (! File::exists($backupPath)) {
            return response()->json(['message'=>'Backup not found'],404);
        }










          
        try {
            $this->setProgress(0,'Restoring backup…');
            $this->restoreFromBackup($backupPath);
            return response()->json(['message'=>'Restored from backup '.$request->date]);
        } catch (\Throwable $e) {
            Log::error('[Updater] Restore failed: '.$e->getMessage());
            return response()->json(['message'=>'Restore failed: '.$e->getMessage()],500);
        }
    }






























    // Helpers...
    private function initProgress(): void
    {
        File::put($this->progressFile, json_encode(['progress'=>0,'message'=>'Starting…']));
    }
    private function setProgress(int $p, string $m): void
    {
        File::put($this->progressFile, json_encode(['progress'=>$p,'message'=>$m]));
    }
    private function validateUpdatePackage(): bool
    {
        $zip=new ZipArchive();
        if($zip->open("{$this->updateDir}/update.zip")!==true) return false;
        foreach(['composer.json','app/','public/'] as $need) {
            if($zip->locateName($need,ZipArchive::FL_NOCASE)===false){$zip->close();return false;}
        }
        $zip->close();return true;
    }
    private function createBackup(): string
    {
        $dst="{$this->backupDir}/".now()->format('Y-m-d_His');
        File::makeDirectory($dst,0755,true);
        foreach(['app','config','resources','routes','public','database'] as $d){
            if(File::exists(base_path($d)))File::copyDirectory(base_path($d),"$dst/$d");
        }
        foreach(['composer.json','composer.lock','.env'] as $f){
            if(File::exists(base_path($f)))File::copy(base_path($f),"$dst/$f");
        }
        return $dst;
    }
    private function extractUpdatePackage(string $zipPath): void
    {
        $zip=new ZipArchive();if($zip->open($zipPath)!==true)throw new \Exception('Cannot open ZIP');
        for($i=0;$i<$zip->numFiles;$i++){ $n=$zip->getNameIndex($i);
            if(Str::startsWith($n,['/','\\'])||Str::contains($n,['..'.DIRECTORY_SEPARATOR,'..\\'])){
                $zip->close();throw new \Exception("Unsafe entry: $n");}}
        $extractDir="{$this->updateDir}/extracted";
        if(File::exists($extractDir))File::deleteDirectory($extractDir);
        File::makeDirectory($extractDir,0755,true);
        $zip->extractTo($extractDir);$zip->close();
        foreach(['app','resources','routes','public'] as $d){
            if(File::exists("$extractDir/$d"))File::copyDirectory("$extractDir/$d",base_path($d));
        }
        File::deleteDirectory($extractDir);
    }
    private function restoreFromBackup(string $src): void
    {
        foreach(['app','config','resources','routes','public','database'] as $d){
            if(File::exists("$src/$d")){
                File::deleteDirectory(base_path($d));
                File::copyDirectory("$src/$d",base_path($d));
            }}
        foreach(['composer.json','composer.lock'] as $f){
            if(File::exists("$src/$f"))File::copy("$src/$f",base_path($f));
        }
    }
    private function getAvailableBackups(): array
    {
        if(!File::exists($this->backupDir))return[];
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
        $u=['B','KB','MB','GB','TB'];$b=max($b,0);
        $pow=floor($b?log($b,1024):0);$pow=min($pow,count($u)-1);
        return round($b/(1<<($pow*10)),$p).' '.$u[$pow];
    }
    private function currentVersion(): string
    {
        $vf=base_path('version.txt');
        if(File::exists($vf))return trim(File::get($vf));
        $c=json_decode(File::get(base_path('composer.json')),true);
        return $c['version']??'1.0.0';
    }
    private function logUpdate(string $backup): void
    {
        File::append($this->logFile,'['.now()."] Update done • backup $backup\n");
    }
}
