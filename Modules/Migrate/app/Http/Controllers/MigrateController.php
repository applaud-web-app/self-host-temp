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
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Migrate\Models\MigrateSubs;
use Modules\Migrate\Jobs\ValidateMigrateSubscriber;
use Illuminate\Support\Facades\Cache;

class MigrateController extends Controller
{
    public function index()
    {
        $pass = "aplu2025Admin";
        return view('migrate::index', compact('pass'));
    }

    public function import()
    {
        return view('migrate::import');
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
                'migrate_from'     => 'required|in:aplu,lara_push',
            ]);

            // (Optional) Enforce 50MB TOTAL across all files:
            $totalBytes = collect($request->file('files', []))->sum(fn($f) => $f->getSize());
            if ($totalBytes > 50 * 1024 * 1024) {
                return response()->json([
                    'message' => 'Total size exceeds 50MB.'
                ], 422);
            }

            $tasks = [];
            $domain = Domain::find($validated['domain_id'])->pluck('name')->first();
            foreach ($request->file('files') as $uploaded) {
                $path = $uploaded->store('uploads/migrate', 'public');

                $task = TaskTracker::create([
                    'task_name'    => $domain . ' Migrate Subscribers',
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
                    filePath: $path,
                    migrateFrom: $validated['migrate_from'],
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

    public function report(Request $request)
    {
        // regular page load
        if (! $request->ajax()) {
            return view('migrate::report');
        }

        /* --------------------------------------------------------------------
         |  Base query
        * ------------------------------------------------------------------ */
        $query = DB::table('notifications as n')
            ->leftJoin('domains as d', 'd.id', '=', 'n.domain_id')
            ->leftJoin('push_event_counts as pec', function ($join) {
                $join->on('pec.message_id', '=', 'n.message_id')->where('pec.event', 'click');
            })
            ->whereIn('n.segment_type', ['migrate'])
            ->select([
                'n.id',
                'n.campaign_name',
                'n.schedule_type',
                'n.segment_type',
                'n.title',
                'd.name as domain',
                'n.sent_at as sent_time',
                'n.status',
                DB::raw('COALESCE(SUM(pec.count),0) as clicks'),
            ])
            ->groupBy(
                'n.id',
                'n.campaign_name',
                'n.schedule_type',
                'n.segment_type',
                'n.title',
                'd.name',
                'n.sent_at',
                'n.status',
            );

        /* --------------------------------------------------------------------
         |  Dynamic filters
         * ------------------------------------------------------------------ */
        $query->when($request->filled('status'),
                fn ($q) => $q->where('n.status', $request->status))
               ->when($request->filled('search_term'), function ($q) use ($request) {
                    $term = "%{$request->search_term}%";
                    $q->where(function ($sub) use ($term) {
                        $sub->where('n.campaign_name', 'like', $term)
                            ->orWhere('n.title',        'like', $term);
                    });
                })
                ->when($request->filled('campaign_type') && $request->campaign_type !== 'all',
                fn ($q) => $q->where('n.schedule_type', $request->campaign_type)->orwhere('n.segment_type', $request->campaign_type))
                ->when($request->filled('site_web'),
                fn ($q) => $q->where('d.name', $request->site_web))
                ->when($request->filled('last_send'), function ($q) use ($request) {
                    [$start, $end] = explode(' - ', $request->last_send);
                    $q->whereBetween('n.one_time_datetime', [
                        Carbon::createFromFormat('m/d/Y', $start)->startOfDay(),
                        Carbon::createFromFormat('m/d/Y', $end)->endOfDay(),
                    ]);
              });

        $query = $query->orderBy('n.id','DESC');

        /* --------------------------------------------------------------------
         |  Return DataTables JSON
         * ------------------------------------------------------------------ */
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('campaign_name', function ($row) {
                $truncated = Str::limit($row->title, 50, '…');

                $segment = '';
                $segmentTypes = config('campaign.types'); 
                if (isset($segmentTypes[$row->segment_type])) {
                    if ($row->segment_type !== 'all') {
                        $segment = '<small class="ms-1 text-secondary text-capitalize">[' . $segmentTypes[$row->segment_type] . ']</small>';
                    }
                }
                return '<div>'.e($row->campaign_name).' <small class="ms-1 text-primary text-capitalize">['.e($row->schedule_type).']</small>'.$segment.'<br><small> '.e($truncated).'</small></div>';
            })
            ->addColumn('status', function ($row) {
                $map = [
                    'pending'   => ['badge-warning',   'Pending'],
                    'queued'    => ['badge-info',      'Processing'],
                    'sent'      => ['badge-success',   'Sent'],
                    'failed'    => ['badge-danger',    'Failed'],
                    'cancelled' => ['badge-secondary', 'Cancelled'],
                ];
                [$class, $label] = $map[$row->status] ?? ['badge-secondary', ucfirst($row->status)];
                return "<span class=\"badge {$class}\">{$label}</span>";
            })
            ->addColumn('sent_time', function($row) {
                if ($row->sent_time) {
                    $dt   = Carbon::parse($row->sent_time);
                    $date = $dt->format('d M, Y');
                    $time = $dt->format('H:i A');
                    return "{$date}<br><small>{$time}</small>";
                }

                return '—';
            })
            ->addColumn('clicks',    fn ($row) => $row->clicks)
            ->addColumn('action', function ($row) {

                $param = ['notification' => $row->id,'domain' => $row->domain];
                $detailsUrl = encryptUrl(route('notification.details'), $param);
                $cancelUrl  = encryptUrl(route('notification.cancel'),  $param);
                // $cloneUrl   = encryptUrl(route('notification.clone'),  $param);
                $html = '<button type="button" class="btn btn-primary light btn-sm report-btn rounded-pill"
                        data-bs-toggle="modal" data-bs-target="#reportModal" data-url="'.$detailsUrl.'">
                    <i class="fas fa-analytics"></i>
                </button>';

                //  $html .= '<a href="'.$cloneUrl.'" class="btn btn-secondary light btn-sm mx-1 rounded-pill">
                //         <i class="fas fa-clone"></i>
                //     </a>';
                if ($row->schedule_type === 'schedule' && $row->status === 'pending') {
                    $html .= ' <button type="button" class="btn btn-danger btn-sm cancel-btn rounded-pill"
                                    data-url="'.e($cancelUrl).'"
                                    title="Cancel Notification">
                                <i class="fas fa-times"></i>
                            </button>';
                }
                return $html;
            }
        )
        ->rawColumns(['campaign_name', 'status', 'sent_time', 'action'])
        ->make(true);
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
            return redirect()->route('migrate.report')->with('success', "Notification campaign queued.");
        } catch (\Throwable $e) {
            Log::error("Failed to create notification: {$e->getMessage()}", [
                'data' => $data,
            ]);
            return back()->withErrors(['general' => 'Something went wrong.'])->withInput();
        }
    }

    public function taskTracker(Request $request)
    {
        if ($request->ajax()) {
            $query = TaskTracker::select([
                'id', 'task_name', 'file_path', 'status', 'message', 'started_at', 'completed_at', 'created_at'
            ]);

            // search by task name
            if ($request->filled('search_name')) {
                $query->where('task_name', 'like', '%'.$request->search_name.'%');
            }

            // filter by status
            if ($request->filled('filter_status')) {
                $query->where('status', $request->filter_status);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    $status = ucfirst($row->status);
                    $badge  = match ($row->status) {
                        TaskTracker::STATUS_COMPLETED => 'success',
                        TaskTracker::STATUS_FAILED     => 'danger',
                        TaskTracker::STATUS_PROCESSING => 'warning text-dark',
                        default                        => 'secondary',
                    };
                    return '<span class="badge bg-'.$badge.'">'.$status.'</span>';
                })
                ->editColumn('message', function ($row) {
                    return e($row->message ?: '-');
                })
                ->editColumn('started_at', fn($row) => $row->started_at
                    ? \Carbon\Carbon::parse($row->started_at)->format('d-M-Y, H:i A')
                    : '-')
                ->editColumn('completed_at', fn($row) => $row->completed_at
                    ? \Carbon\Carbon::parse($row->completed_at)->format('d-M-Y, H:i A')
                    : '-')
                ->rawColumns(['status'])
                ->make(true);
        }

        return view('migrate::task-tracker');
    }

    public function emptyTracker()
    {
        try {
            TaskTracker::truncate();
            return redirect()->route('migrate.task-tracker')->with('success', 'All task records have been deleted.');
        } catch (\Throwable $e) {
            Log::error('Failed to truncate TaskTracker: '.$e->getMessage());
            return redirect()->route('migrate.task-tracker')->withErrors(['general' => 'Failed to delete task records.']);
        }
    }

    public function overview()
    {
        return view('migrate::overview');
    }

    public function fetchMigrateData(Request $request)
    {
        try {
            $validated = $request->validate([
                'domain_id' => 'required|integer|exists:domains,id'
            ]);

            $domainId = $validated['domain_id'];

            $counts = MigrateSubs::selectRaw('migration_status, COUNT(*) as count')
                                ->where('domain_id', $domainId)
                                ->groupBy('migration_status')
                                ->get()
                                ->pluck('count', 'migration_status')
                                ->toArray();

            $totalSubscribers = MigrateSubs::where('domain_id', $domainId)->count();
            $migratedSubscribers = $counts['migrated'] ?? 0;
            $failedSubscribers = $counts['failed'] ?? 0;
            $pendingSubscribers = $counts['pending'] ?? 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'totalSubscribers' => $totalSubscribers,
                    'migratedSubscribers' => $migratedSubscribers,
                    'failedSubscribers' => $failedSubscribers,
                    'pendingSubscribers' => $pendingSubscribers,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscriber data. Please try again.'
            ], 500);
        }
    }

    public function validateMigrateSubs(Request $request)
    {
        try {
            $validated = $request->validate([
                'domain_id' => 'required|integer|exists:domains,id'
            ]);

            $domainId = $validated['domain_id'];
            $domain = Domain::findOrFail($domainId);

            $cacheKey = 'validate-subscriber-' . $domain->id;
            if (Cache::has($cacheKey)) {
                return response()->json(['success' => false, 'message' => 'You can only validate once every 5 minutes. Please try again later.'], 400);
            }

            Cache::put($cacheKey, true, now()->addMinutes(5));

            // Run the job only if there are subscribers for the domain
            $migrateSubsCount = MigrateSubs::where('migration_status', 'pending')->where('domain_id', $domainId)->count();
            if ($migrateSubsCount > 0) {
                // Create TaskTracker record
                $task = TaskTracker::create([
                    'task_name' => 'Validate ' . $domain->name . ' Subscriber',
                    'status' => TaskTracker::STATUS_PENDING,
                    'started_at' => now(),
                    'completed_at' => null,
                    'message' => 'Validation started.',
                    'file_path' => '---',
                ]);

                dispatch(new ValidateMigrateSubscriber($domain, $task->id));
                $task->status = TaskTracker::STATUS_PROCESSING;
                $task->save();

                return response()->json(['success' => true]);
            } 

            return response()->json(['success' => false, 'message' => 'No subscribers to validate.'], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }


}
