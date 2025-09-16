<?php

namespace Modules\AdvanceSegmentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Segment;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\ValidationException;

class AdvanceSegmentationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = Segment::select(['id','name','domain','type','status','created_at'])->where('status','!=',2)->whereIn('type',['url','time']);
            if ($request->filled('search_name')) {
                $query->where('name', 'like', '%'.$request->search_name.'%');
            }

            if ($request->filled('filter_status') && in_array($request->filter_status, [1, 0])) {
                $query->where('status', $request->filter_status);
            }

            if ($request->filled('filter_type') && in_array($request->filter_type, ['url','time'])) {
                $query->where('type', $request->filter_type);
            }

            $query->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('status', function ($row) {

                    $updateStatusUrl = route('segmentation.update-status');
                    $param = ['id' => $row->id];
                    $integrateUpdateStatusUrl = encryptUrl($updateStatusUrl, $param);

                    $checked = $row->status ? 'checked' : '';
                    return '<div class="form-check form-switch">
                            <input class="form-check-input toggle-status"
                                    data-url="'.$integrateUpdateStatusUrl.'"
                                    type="checkbox" '.$checked.'>
                            </div>';
                })
                ->editColumn('created_at',
                    fn ($row) => $row->created_at->format('d M, Y')
                )
                ->editColumn('type',
                    fn ($row) => ucfirst($row->type)
                )
                ->addColumn('actions', function ($row) {
                    
                    $param = ['id' => $row->id];
                    $integrateRemoveUrl = encryptUrl(route('segmentation.update-status'), $param);
                    $integrateViewUrl = encryptUrl(route('advance-segmentation.info'), $param);

                    return '<button type="button" data-url="'.$integrateViewUrl.'" class="btn btn-sm btn-info me-1 view-btn">
                                <i class="far fa-eye"></i>
                            </button>
                            <button type="button" data-url="'.$integrateRemoveUrl.'" class="btn btn-sm btn-danger me-1 remove-btn">
                                <i class="far fa-trash"></i>
                            </button>';
                })
                ->rawColumns(['status', 'type', 'actions'])
                ->make(true);
        }

        return view('advancesegmentation::index');
    }

    public function create()
    {
        $countriesStates = [];
        return view('advancesegmentation::create', compact('countriesStates'));
    }

    public function store(Request $request)
    {
        $type = $request->input('segment_type');

        if ($type === 'time') {
            $data = $request->validate([
                'segment_name'   => 'required|string|max:255',
                'domain_name'    => 'required|exists:domains,name',
                'segment_type'   => 'required|in:time,url',
                'start_datetime' => 'required|date|before_or_equal:now',
                'end_datetime'   => 'required|date|after:start_datetime|before_or_equal:now',
            ]);
        } elseif ($type === 'url') {
            $data = $request->validate([
                'segment_name' => 'required|string|max:255',
                'domain_name'  => 'required|exists:domains,name',
                'segment_type' => 'required|in:time,url',
                'urls'         => 'required|array|min:1|max:10',
                'urls.*'       => 'string|max:2048',
            ]);
        } else {
            return back()->withErrors(['segment_type' => 'Invalid segment type.']);
        }

        return DB::transaction(function () use ($type, $data) {
            // Insert segment
            $segmentId = DB::table('segments')->insertGetId([
                'name'       => $data['segment_name'],
                'domain'     => $data['domain_name'],
                'type'       => $type,
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($type === 'time') {
                DB::table('segment_time_rules')->insert([
                    'segment_id' => $segmentId,
                    'start_at'   => $data['start_datetime'],
                    'end_at'     => $data['end_datetime'],
                ]);
            } else {
                $norm = collect($data['urls'])
                    ->map(fn($u) => [
                        'segment_id'     => $segmentId,
                        'url'            => $u,
                    ])
                    ->values()
                    ->all();

                if (!empty($norm)) {
                    DB::table('segment_url_rules')->insert($norm);
                }
            }

            return redirect()
                ->route('advance-segmentation.index')
                ->with('success', 'Segmentation created successfully!');
        });
    }

    public function urlList(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['required','string','max:255'],
        ]);

        $domain = $validated['domain'];

        // meta has no timestamps in your migration, so rely on head.created_at
        $rows = DB::table('push_subscriptions_meta as m')
            ->join('push_subscriptions_head as h', 'h.id', '=', 'm.head_id')
            ->where('h.domain', $domain)
            ->whereNotNull('m.subscribed_url')
            ->where('m.subscribed_url', '!=', '')
            ->selectRaw("
                LOWER(TRIM(BOTH '/' FROM m.subscribed_url)) AS url,
                MAX(h.created_at) AS last_seen
            ")
            ->groupBy('url')
            ->orderByDesc('last_seen')
            ->limit(500)
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $rows->map(fn($r) => ['id' => $r->url, 'text' => $r->url]),
        ]);
    }

    public function refreshData(Request $request)
    {
        $type = $request->input('segment_type');

        if ($type === 'time') {
            $data = $request->validate([
                'segment_name'   => 'required|string|max:255',
                'domain_name'    => 'required|exists:domains,name',
                'segment_type'   => 'required|in:time,url',
                'start_datetime' => 'required|date|before_or_equal:now',
                'end_datetime'   => 'required|date|after:start_datetime|before_or_equal:now',
            ]);

            // Use head.created_at (indexed combo idx_psh_created_origin)
            $count = DB::table('push_subscriptions_head as h')
                ->join('push_subscriptions_meta as m', 'm.head_id', '=', 'h.id')
                ->where('h.status', 1)
                ->where('h.parent_origin', $data['domain_name'])
                ->whereBetween('h.created_at', [$data['start_datetime'], $data['end_datetime']])
                ->distinct('h.id')
                ->count('h.id');

            return response()->json(['count' => (int)$count]);
        }

        if ($type === 'url') {
            $data = $request->validate([
                'segment_name' => 'required|string|max:255',
                'domain_name'  => 'required|exists:domains,name',
                'segment_type' => 'required|in:time,url',
                'urls'         => 'required|array|min:1|max:10',
                'urls.*'       => 'string|max:2048',
            ]);

            // Normalize URLs to match the SQL normalization
            $urls = collect($data['urls'])
                ->map(fn($u) => strtolower(trim($u, "/ \t\n\r\0\x0B")))
                ->unique()
                ->values()
                ->all();

            $count = DB::table('push_subscriptions_head as h')
                ->join('push_subscriptions_meta as m', 'm.head_id', '=', 'h.id')
                ->where('h.status', 1)
                ->where('h.parent_origin', $data['domain_name'])
                ->whereIn(DB::raw("LOWER(TRIM(BOTH '/' FROM m.subscribed_url))"), $urls)
                ->distinct('h.id')
                ->count('h.id');

            return response()->json(['count' => (int)$count]);
        }

        return response()->json(['count' => 0], 422);
    }
    
    public function info(Request $request)
    {
        try {
            // Validate request with proper error messages
            $validated = $request->validate([
                'eq' => 'required|string',
            ], [
                'eq.required' => 'Encrypted parameter is required',
                'eq.string'   => 'Invalid parameter format',
            ]);

            // Decrypt ID with error handling
            $response = decryptUrl($request->eq);
            if (!isset($response['id'])) {
                throw new \Exception('Invalid segment identifier');
            }

            $id = (int) $response['id'];

            // Fetch the base segment (only url/time types)
            $segment = DB::table('segments')
                ->select('id', 'name', 'domain', 'type', 'status', 'created_at')
                ->where('id', $id)
                ->whereIn('type', ['url', 'time'])
                ->first();

            if (!$segment) {
                abort(404, 'Segment not found');
            }

            if ($request->ajax()) {
                // Base payload
                $payload = [
                    'id'           => $segment->id,
                    'name'         => $segment->name,
                    'domain'       => $segment->domain,
                    'type'         => $segment->type,
                    'created_at'   => \Carbon\Carbon::parse($segment->created_at)->format('d M Y, h:i A'),
                    'status'       => (int) $segment->status,
                    'status_badge' => ((int)$segment->status) ? 'success' : 'secondary',
                    'status_text'  => ((int)$segment->status) ? 'Active' : 'Inactive',
                ];

                if ($segment->type === 'time') {
                    // Expect single row per segment_id (as per your store logic)
                    $timeRule = DB::table('segment_time_rules')
                        ->where('segment_id', $segment->id)
                        ->select('start_at', 'end_at')
                        ->first();

                    if ($timeRule) {
                        $start = \Carbon\Carbon::parse($timeRule->start_at);
                        $end   = \Carbon\Carbon::parse($timeRule->end_at);

                        $payload['time'] = [
                            'start_at'        => $start->toDateTimeString(),
                            'end_at'          => $end->toDateTimeString(),
                            'start_at_human'  => $start->format('d M Y, h:i A'),
                            'end_at_human'    => $end->format('d M Y, h:i A'),
                        ];
                    } else {
                        $payload['time'] = null; // no rule configured
                    }
                }

                if ($segment->type === 'url') {
                    // List of URLs for this segment
                    $urls = DB::table('segment_url_rules')
                        ->where('segment_id', $segment->id)
                        ->orderBy('id')
                        ->pluck('url')
                        ->map(function ($u) {
                            // Mirror the normalization used elsewhere in your controller
                            return strtolower(trim($u, "/ \t\n\r\0\x0B"));
                        })
                        ->unique()
                        ->values();

                    $payload['urls'] = $urls;
                    $payload['urls_count'] = $urls->count();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Segment details loaded successfully',
                    'data'    => $payload,
                ]);
            }

            abort(404, 'Page not found');
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load segment details: ' . $th->getMessage(),
            ], 500);
        }
    }

}
