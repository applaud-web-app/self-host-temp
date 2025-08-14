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
use Illuminate\Support\Facades\DB;
use App\Models\PushEventCount;
use App\Models\Segment;
use Illuminate\Http\JsonResponse;
use App\Jobs\SendSegmentNotificationJob;

class NotificationController extends Controller
{
    public function view(Request $request)
    {
        // regular page load
        if (! $request->ajax()) {
            return view('notification.view');
        }

        /* --------------------------------------------------------------------
         |  Base query
         * ------------------------------------------------------------------ */
        $query = DB::table('notifications as n')
            ->leftJoin('domain_notification as dn', 'n.id', '=', 'dn.notification_id')
            ->leftJoin('domains as d', 'd.id', '=', 'dn.domain_id')
            ->leftJoin('push_event_counts as pec', function ($join) {
                $join->on('pec.message_id', '=', 'n.message_id')
                    //  ->on('pec.domain',      '=', 'd.name')
                     ->where('pec.event', 'click');
            })
            ->select([
                'n.id',
                'n.campaign_name',
                'n.schedule_type',
                'n.segment_type',
                'n.title',
                'd.name as domain',
                'dn.sent_at as sent_time',
                'dn.status',
                DB::raw('COALESCE(SUM(pec.count),0) as clicks'),
            ])
            ->groupBy(
                'n.id',
                'n.campaign_name',
                'n.schedule_type',
                'n.segment_type',
                'n.title',
                'd.name',
                'dn.sent_at',
                'dn.status',
            );

        /* --------------------------------------------------------------------
         |  Dynamic filters
         * ------------------------------------------------------------------ */
        $query->when($request->filled('status'),
                fn ($q) => $q->where('dn.status', $request->status))
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
                $cloneUrl   = encryptUrl(route('notification.clone'),  $param);
                $html = '<button type="button" class="btn btn-primary light btn-sm report-btn rounded-pill"
                        data-bs-toggle="modal" data-bs-target="#reportModal" data-url="'.$detailsUrl.'">
                    <i class="fas fa-analytics"></i>
                </button>';

                 $html .= '<a href="'.$cloneUrl.'" class="btn btn-secondary light btn-sm mx-1 rounded-pill">
                        <i class="fas fa-clone"></i>
                    </a>';
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

    public function cancel(Request $request)
    {
        // 1) Validate the encrypted token
        $request->validate([
            'eq' => 'required|string',
        ]);

        // 2) Decrypt payload
        try {
            $payload        = decryptUrl($request->input('eq'));
            $notificationId = $payload['notification'];
            $domainName     = $payload['domain'];
        } catch (\Throwable $e) {
            Log::warning("Cancel failed: invalid link [{$e->getMessage()}]");
            return response()->json([
                'status'  => false,
                'message' => 'Invalid or expired link.',
            ], 422);
        }

        // 3) Attempt to mark pending → cancelled
        $affected = DB::table('domain_notification as dn')
            ->join('domains as d', 'd.id', '=', 'dn.domain_id')
            ->where('dn.notification_id', $notificationId)
            ->where('dn.status',          'pending')
            ->where('d.name',             $domainName)
            ->update([
                'dn.status'  => 'cancelled',
                'dn.sent_at' => Carbon::now(),
            ]);

        if (! $affected) {
            return response()->json([
                'status'  => false,
                'message' => 'Notification not pending or already processed.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Scheduled notification has been cancelled.',
        ]);
    }

    public function details(Request $request)
    {
        try {
            $request->validate(['eq' => 'required|string']);

            // decryptUrl() returns ['notification' => …, 'domain' => …]
            $payload = decryptUrl($request->eq);
            $domain  = $payload['domain'];
            $id      = $payload['notification'];

            $notification = Notification::where('id', $id)->firstOrFail();

            // counts ------------------------------------------------------------
            $counts = PushEventCount::where('message_id', $notification->message_id)
                        // ->where('domain', $domain)
                        ->whereIn('event', ['received', 'click'])
                        ->pluck('count', 'event');

            $received = (int) $counts->get('received', 0);
            $clicked  = (int) $counts->get('click', 0);
            $delivered = (int) $notification->success_count;   // total sent/delivered

            // buttons -----------------------------------------------------------
            $btns = [];
            if ($notification->btn_1_title && $notification->btn_1_url) {
                $btns[] = ['title' => $notification->btn_1_title,
                        'url'   => $notification->btn_1_url];
            }
            if ($notification->btn_title_2 && $notification->btn_url_2) {
                $btns[] = ['title' => $notification->btn_title_2,
                        'url'   => $notification->btn_url_2];
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'title'        => $notification->title,
                    'description'  => $notification->description,
                    'banner_image' => $notification->banner_image ?: asset('images/default.png'),
                    'banner_icon'  => $notification->banner_icon  ?: asset('images/push/icons/alarm-1.png'),
                    'link'         => $notification->target_url,
                    'btns'         => $btns,                       // may be empty
                    'analytics'    => [
                        'delivered' => $delivered,
                        'received'  => $received,
                        'clicked'   => $clicked,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Report-modal failed: '.$e->getMessage());
            return response()->json(['status' => false], 422);
        }
    }

    public function create()
    {
        return view('notification.create');
    }

    public function clone(Request $request)
    {
        try {
            $request->validate([
                'eq' => 'required|string'
            ]);

            $payload = decryptUrl($request->eq);
            $domain  = $payload['domain'];
            $id      = $payload['notification'];

            $notification = Notification::where('id', $id)->firstOrFail();
            return view('notification.clone', compact('notification'));
        } catch (\Throwable $th) {
            \Log::error('Clone failed: '.$th->getMessage());
            return response()->back()->with('error', 'Failed to clone notification. Please try again.');
        }
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
        // helper to trim + decode
        $clean = function(string $value): string {
            return html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        };

        // Title
        if (preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {
            $title = $clean($m[1]);
        } else {
            $title = '';
        }

        // <meta name="description">
        if (preg_match(
            '/<meta\s+name=["\']description["\'][^>]*content=["\'](.*?)["\']/is',
            $html,
            $m2
        )) {
            $description = $clean($m2[1]);
        } else {
            $description = '';
        }

        // og:image
        if (preg_match(
            '/<meta\s+property=["\']og:image["\'][^>]*content=["\'](.*?)["\']/is',
            $html,
            $m3
        )) {
            $image = $clean($m3[1]);
        }
        // fallback to <link rel="image_src">
        elseif (preg_match(
            '/<link\s+rel=["\']image_src["\'][^>]*href=["\'](.*?)["\']/is',
            $html,
            $m4
        )) {
            $image = $clean($m4[1]);
        } else {
            $image = '';
        }

        return compact('title', 'description', 'image');
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
            'banner_image'      => 'nullable|url',
            'banner_icon'       => 'nullable|url',
            'schedule_type'     => 'required|in:Instant,Schedule',
            'one_time_datetime' => 'required_if:schedule_type,Schedule|nullable|date',
            'segment_type'      => 'required|in:all,particular',
            'domain_name'       => 'required_if:segment_type,all|array|min:1',
            'domain_name.*'     => 'required_if:segment_type,all|string|exists:domains,name',
            'cta_enabled'       => 'required|in:0,1',
            'btn_1_title'       => 'nullable|required_if:cta_enabled,1|string|max:255',
            'btn_1_url'         => 'nullable|required_if:cta_enabled,1|url',
            'btn_title_2'       => 'nullable|string|max:255',
            'btn_url_2'         => 'nullable|required_with:btn_title_2|url',
            'segment_id'        => 'nullable|required_if:segment_type,particular|exists:segments,id',
        ], [], [
            'one_time_datetime' => 'one-time date & time',
            'btn_1_title'       => 'Button 1 title',
            'btn_1_url'         => 'Button 1 URL',
            'btn_title_2'       => 'Button 2 title',
            'btn_url_2'         => 'Button 2 URL',
        ]);

        $defaults = [
            'banner_image' => asset('images/default.png'),
            'banner_icon'  => asset('images/push/icons/alarm-1.png'),
        ];

        if($data['banner_image'] === $defaults['banner_image']) {
            $data['banner_image'] = null;
        }

        try {
            $notification = Notification::create([
                'target_url'        => $data['target_url'],
                'campaign_name'     => 'CAMP#'.random_int(1000,9999),
                'title'             => $data['title'],
                'description'       => $data['description'],
                'banner_image'      => $data['banner_image'] ?? null,
                'banner_icon'       => $data['banner_icon']  ?? null,
                'schedule_type'     => strtolower($data['schedule_type']),
                'one_time_datetime' => $data['schedule_type'] === 'Schedule' ? $data['one_time_datetime'] : null,
                'message_id'        => Str::uuid(),
                'btn_1_title'       => $data['btn_1_title'] ?? null,
                'btn_1_url'         => $data['btn_1_url'] ?? null,
                'btn_title_2'       => $data['btn_title_2'] ?? null,
                'btn_url_2'         => $data['btn_url_2'] ?? null,
                'segment_type'      => $data['segment_type'],
                'segment_id'        => $data['segment_id'] ?? null,
            ]);

            if ($data['segment_type'] === 'all') {
                $ids = Domain::whereIn('name', $data['domain_name'])->where('status', 1)->pluck('id')->all();
                $notification->domains()->sync($ids);
            }else{
                // particular segment → attach its single domain
                $segment = Segment::find($data['segment_id']);
                if ($segment && $segment->domain) {
                    $domain = Domain::where('name', $segment->domain)->first();
                    if ($domain) {
                        $notification->domains()->sync([$domain->id]);
                    }
                }
            }

            // Instant: fire off immediately
            if ($notification->schedule_type === 'instant') {
                if ($data['segment_type'] === 'all') {
                    SendNotificationJob::dispatch($notification->id);
                } else {
                    SendSegmentNotificationJob::dispatch(
                        $notification->id,
                        $notification->segment_id
                    );
                }
            }

            return redirect()->route('notification.view')->with('success', "Notification {$notification->campaign_name} queued.");
        } catch (\Throwable $e) {
            Log::error("Failed to create notification: {$e->getMessage()}", [
                'data' => $data,
            ]);
            return back()->withErrors(['general'=>'Something went wrong.'])->withInput();
        }
    }

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
