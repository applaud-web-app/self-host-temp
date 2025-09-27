<?php

namespace Modules\Migrate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Migrate\Models\TaskTracker;
use Modules\Migrate\Jobs\ProcessSubscriberFileJob;
use Modules\Migrate\Jobs\DispatchNotifications;
use Illuminate\Support\Facades\Log;
use App\Models\Domain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MigrateController extends Controller
{
    public function index()
    {
        return view('migrate::index');
    }

    public function showTask(TaskTracker $task)
    {
        return response()->json([
            'id' => $task->id,
            'status' => $task->status,
            'message' => $task->message,
            'started_at' => $task->started_at,
            'completed_at' => $task->completed_at,
        ]);
    }

    public function upload(Request $request)
    {
        try {
            // Validate: allow one or multiple Excel files
            $validated = $request->validate([
                'domain_id'     => 'required|exists:domains,id',
                'files'         => 'required',
                'files.*'       => 'file|mimes:xlsx,xls|max:51200',
            ]);

            // (Optional) Enforce 50MB TOTAL across all files:
            $totalBytes = collect($request->file('files', []))->sum(fn($f) => $f->getSize());
            if ($totalBytes > 50 * 1024 * 1024) {
                return response()->json([
                    'message' => 'Total size exceeds 50MB.'
                ], 422);
            }

            $tasks = [];

            foreach ($request->file('files') as $uploaded) {
                $path = $uploaded->store('uploads/migrate', 'public');

                $task = TaskTracker::create([
                    'task_name'    => 'Migrate Subscribers',
                    'file_path'    => $path,
                    'status'       => TaskTracker::STATUS_PENDING,
                    'message'      => null,
                    'started_at'   => null,
                    'completed_at' => null,
                ]);

                // Dispatch queue job
                dispatch(new ProcessSubscriberFileJob(
                    taskId:   $task->id,
                    domainId: (int) $validated['domain_id'],
                    storageDisk: 'public',
                    filePath: $path
                ));

                $tasks[] = $task->id;
            }

            return response()->json([
                'message' => 'File(s) uploaded successfully. Import task(s) are in progress.',
                'task_ids' => $tasks,
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
            Log::error('File upload error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'There was an error uploading the file. Please try again later.'
            ], 500);
        }
    }

    public function report()
    {
        // You can pass real migration results later
        return view('migrate::report');
    }

    public function sendNotification()
    {
        // Later you can implement sending logic here
        return view('migrate::send-notification');
    }

    public function store(Request $request)
    {
        // Always cast CTA checkbox
        if (! $request->has('cta_enabled')) {
            $request->merge(['cta_enabled' => 0]);
        }

        // 1) Validate
        $data = $request->validate([
            'target_url'        => 'required|url',
            'title'             => 'required|string|max:100',
            'description'       => 'required|string|max:200',
            'banner_src_type'   => 'required|in:url,upload',
            'banner_image'      => 'nullable|exclude_if:banner_src_type,upload|url',
            'banner_image_file' => 'nullable|exclude_if:banner_src_type,url|file|image|mimes:jpg,jpeg,png,gif,webp|max:1024',
            'banner_icon'       => 'nullable|url',
            'domain_name'       => 'required|array|min:1',
            'domain_name.*'     => 'required|string|exists:domains,name',
            'cta_enabled'       => 'required|in:0,1',
            'btn_1_title'       => 'nullable|required_if:cta_enabled,1|string|max:255',
            'btn_1_url'         => 'nullable|required_if:cta_enabled,1|url',
            'btn_title_2'       => 'nullable|string|max:255',
            'btn_url_2'         => 'nullable|required_with:btn_title_2|url',
        ], [], [
            'btn_1_title'       => 'Button 1 title',
            'btn_1_url'         => 'Button 1 URL',
            'btn_title_2'       => 'Button 2 title',
            'btn_url_2'         => 'Button 2 URL',
        ]);

        if ($data['banner_src_type'] === 'upload' && $request->hasFile('banner_image_file')) {
            $dir = public_path('uploads/banner');
            if (! File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            $ext = $request->file('banner_image_file')->getClientOriginalExtension();
            $filename = (string) Str::uuid() . '.' . $ext;
            $request->file('banner_image_file')->move($dir, $filename);

            // override banner_image with the full public URL to the uploaded file
            $data['banner_image'] = url('uploads/banner/' . $filename);
        } else {
            $data['banner_image'] = $data['banner_image'] ?? null;
        }
        unset($data['banner_image_file'], $data['banner_src_type']);

        // Fetch domain IDs
        $ids = Domain::whereIn('name', $data['domain_name'])->where('status', 1)->pluck('id')->all();

        try {
            dispatch(new DispatchNotifications($data, $ids))->onQueue('migrate-create-notifications');
            return redirect()->route('notification.view')->with('success', "Notification campaign queued.");
        } catch (\Throwable $e) {
            Log::error("Failed to create notification: {$e->getMessage()}", [
                'data' => $data,
            ]);
            return back()->withErrors(['general' => 'Something went wrong.'])->withInput();
        }
    }


    // migrate notify
    private const NODE_SERVICE_URL = 'http://127.0.0.1:3600/migrate-notification';
    public function  migrateNotify(Request $request)
    {
        try {
            // Fetch all subscriptions from the 'migrate_subs' table.
            // Using DB::table for a quick, direct query.
            $subscriptions = DB::table('migrate_subs')->get();

            if ($subscriptions->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No subscriptions found to notify.',
                    'sent_count' => 0
                ]);
            }

            // Prepare the data to be sent. The Node.js service expects an array of objects.
            $dataToSend = $subscriptions->map(function ($sub) {
                return [
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key, // Assuming this is the VAPID public key
                    'privateKey' => $sub->private_key, // Assuming this is the VAPID private key
                    'auth' => $sub->auth,
                    'p256dh' => $sub->p256dh
                ];
            });

            // Make a POST request to the Node.js service using Laravel's HTTP Client.
            $response = Http::timeout(30)->post(self::NODE_SERVICE_URL, [
                'subscriptions' => $dataToSend->toArray()
            ]);

            // Check if the request to the Node.js service was successful.
            if ($response->successful()) {
                $responseData = $response->json();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Notifications have been triggered successfully.',
                    'response_from_node' => $responseData
                ]);
            } else {
                // Log the error and return an appropriate response.
                Log::error('Failed to communicate with Node.js service.', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to trigger notifications. Node.js service responded with an error.',
                    'response_from_node' => $response->body()
                ], $response->status());
            }

        } catch (\Exception $e) {
            // Catch any other exceptions and return an error.
            Log::error('An error occurred during notification migration.', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred.',
                'error_details' => $e->getMessage()
            ], 500);
        }
    }

}
