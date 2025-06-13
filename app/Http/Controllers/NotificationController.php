<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\Domain;
use App\Jobs\SendNotificationJob;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function view(Request $request)
    {
        // AJAX request? return JSON for DataTables
        if ($request->ajax()) {
            $query = Notification::with('domains')->select('notifications.*');

            // 1) filter by campaign name
            if ($request->filled('campaign_name')) {
                $query->where('campaign_name','like','%'.$request->campaign_name.'%');
            }

            // 2) filter by status (assumes you have a `status` column)
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // 3) filter by domain URL
            if ($request->filled('site_web')) {
                $query->whereHas('domains', function($q) use($request){
                    $q->where('domain_url','like','%'.$request->site_web.'%');
                });
            }

            // 4) date-range on “sent time” (one_time_datetime)
            if ($request->filled('last_send')) {
                [$start, $end] = explode(' - ', $request->last_send);
                $start = Carbon::createFromFormat('m/d/Y', $start)->startOfDay();
                $end   = Carbon::createFromFormat('m/d/Y', $end)->endOfDay();
                $query->whereBetween('one_time_datetime', [$start, $end]);
            }

            // 5) campaign type radio
            if ($request->filled('campaign_type') && $request->campaign_type!=='all') {
                $query->where('schedule_type',$request->campaign_type);
            }

            return DataTables::of($query)
                ->addIndexColumn()

                // flatten domains into a comma list
                ->addColumn('domain', fn($row) => 
                    $row->domains->pluck('domain_url')->implode(', ')
                )

                // choose the right “sent time” col (one-time vs recurring)
                ->addColumn('sent_time', function($row){
                    return $row->one_time_datetime
                        ? $row->one_time_datetime->format('Y-m-d H:i')
                        : ($row->recurring_start_date
                            ? $row->recurring_start_date->format('Y-m-d')
                            : '');
                })
                ->addColumn('clicks', fn($row) => $row->clicks_count ?? 0)

                // actions column
                ->addColumn('action', fn($row) =>
                    '<a href="'.route('notification.view',$row->id)
                    .'" class="btn btn-sm btn-primary">View</a>'
                )
                ->rawColumns(['action'])
                ->make(true);
        }

        // initial page load: pass domain list for the dropdown
        $domains = Domain::pluck('name')->sortBy('name');
        return view('notification.view', compact('domains'));
    }

    public function create()
    {
        $domains = Domain::where('status', 1)->orderBy('name')->get();
        return view('notification.create', compact('domains'));
    }

    public function fetchMeta(Request $request)
    {
        $request->validate([
            'target_url' => 'required|url',
        ]);

        // 1) Fetch the page with a timeout
        try {
            $response = Http::timeout(5)->get($request->input('target_url'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch the URL.',
            ]);
        }

        if (! $response->ok()) {
            return response()->json([
                'success' => false,
                'message' => 'URL returned HTTP ' . $response->status(),
            ]);
        }

        // 2) Parse metadata via regex
        $html = $response->body();
        $meta = $this->parseMetaRegex($html);

        // 3) If nothing found, treat as failure
        if (empty($meta['title']) && empty($meta['description']) && empty($meta['image'])) {
            return response()->json([
                'success' => false,
                'message' => 'No usable metadata found on that page.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $meta,
        ]);
    }

    /**
     * Extracts title, meta description and og:image (or link[rel=image_src]) via regex
     */
    protected function parseMetaRegex(string $html): array
    {
        // Title
        if (preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {
            $title = trim($m[1]);
        } else {
            $title = '';
        }

        // <meta name="description">
        if (preg_match(
            '/<meta\s+name=["\']description["\'][^>]*content=["\'](.*?)["\']/is',
            $html,
            $m2
        )) {
            $description = trim($m2[1]);
        } else {
            $description = '';
        }

        // og:image
        if (preg_match(
            '/<meta\s+property=["\']og:image["\'][^>]*content=["\'](.*?)["\']/is',
            $html,
            $m3
        )) {
            $image = trim($m3[1]);
        }
        // fallback to <link rel="image_src">
        elseif (preg_match(
            '/<link\s+rel=["\']image_src["\'][^>]*href=["\'](.*?)["\']/is',
            $html,
            $m4
        )) {
            $image = trim($m4[1]);
        } else {
            $image = '';
        }

        return compact('title', 'description', 'image');
    }

    public function store(Request $request)
    {
        // 1) Validate
        $data = $request->validate([
            'target_url'           => 'required|url',
            'title'                => 'required|string|max:255',
            'description'          => 'required|string',
            'banner_image'         => 'nullable|url',
            'banner_icon'          => 'nullable|url',
            'schedule_type'        => 'required|in:Instant,Schedule',
            'one_time_datetime'    => 'required_if:schedule_type,Schedule|nullable|date',
            'domain_name'          => 'required|array|min:1',
            'domain_name.*'        => 'required|string|exists:domains,name',

            // our new CTA checkbox
            'cta_enabled'       => 'sometimes|boolean',

            // button 1 required only if CTA enabled
            'btn_1_title'       => 'required_if:cta_enabled,1|string|max:255',
            'btn_1_url'         => 'required_if:cta_enabled,1|url',

            // button 2 only required if one of its pair is present
            'btn_title_2'       => 'nullable|required_with:btn_url_2|string|max:255',
            'btn_url_2'         => 'nullable|required_with:btn_title_2|url',
            
        ], [], [
            'one_time_datetime' => 'one‐time date & time',
            'btn_1_title'       => 'Button 1 title',
            'btn_1_url'         => 'Button 1 URL',
            'btn_title_2'       => 'Button 2 title',
            'btn_url_2'         => 'Button 2 URL',
        ]);

        try {
            // 2) Generate a campaign name
            $data['campaign_name'] = 'CAMP#' . random_int(1000, 9999);

            // 3) Create Notification
            $notification = Notification::create([
                'target_url'           => $data['target_url'],
                'campaign_name'        => $data['campaign_name'],
                'title'                => $data['title'],
                'description'          => $data['description'],
                'banner_image'         => $data['banner_image']  ?? null,
                'banner_icon'          => $data['banner_icon']   ?? null,
                'schedule_type'        => strtolower($data['schedule_type']),
                'one_time_datetime'    => $data['schedule_type']==='Schedule' ? $data['one_time_datetime'] : null,
                'message_id'           => Str::uuid(),
                
                // CTA fields (will be null if not enabled)
                'btn_1_title'        => $data['btn_1_title']   ?? null,
                'btn_1_url'          => $data['btn_1_url']     ?? null,
                'btn_title_2'        => $data['btn_title_2']   ?? null,
                'btn_url_2'          => $data['btn_url_2']     ?? null,
            ]);

            // 4) Attach domains
            $ids = Domain::whereIn('name', $data['domain_name'])->pluck('id');
            $notification->domains()->sync($ids);

            // 5) Dispatch send job (immediately for Instant, or you could delay for Schedule)
            if ($notification->schedule_type === 'schedule' && $notification->one_time_datetime) {
                // SendNotificationJob::dispatch($notification->id)->delay(Carbon::parse($notification->one_time_datetime));
            } else {
                SendNotificationJob::dispatch($notification->id);
            }

            return redirect()->route('notification.view')->with('success', "Notification “{$data['campaign_name']}” queued for sending.");
        } catch (\Throwable $th) {
            Log::error('Failed to create notification: '.$th->getMessage(), [
                'data'      => $data,
                'exception' => $th,
            ]);
            return back()->withErrors(['general' => 'Failed to create notification. Please try again later.'])->withInput($data);
        }
    }

    /**
     * GET  /notifications/{notification}
     * Show details of a single notification.
     */
    public function show(Notification $notification)
    {
        $notification->load('domains');
        return view('notifications.show', compact('notification'));
    }

    /**
     * POST /notifications/{notification}/send
     * Re‐dispatch an existing notification.
     */
    public function send(Notification $notification)
    {
        SendNotificationJob::dispatch($notification);

        return back()->with('success', 'Notification re-queued for sending.');
    }
}
