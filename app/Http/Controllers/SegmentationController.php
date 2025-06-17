<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Segment;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Cache;
use App\Models\PushSubscriptionMeta;
use Illuminate\Support\Facades\DB;
use App\Models\SegmentDeviceRule;
use App\Models\SegmentGeoRule;
use App\Models\Domain;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class SegmentationController extends Controller
{
    public function view(Request $request)
    {
        if ($request->ajax()) {

            $query = Segment::select(['id','name','domain','type','status','created_at']);
            if ($request->filled('search_name')) {
                $query->where('name', 'like', '%'.$request->search_name.'%');
            }

            if ($request->filled('filter_status') && in_array($request->filter_status, [1, 0])) {
                $query->where('status', $request->filter_status);
            }

            if ($request->filled('filter_type') && in_array($request->filter_type, ['geo', 'device'])) {
                $query->where('type', $request->filter_type);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('status', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="form-check form-switch">
                            <input class="form-check-input toggle-status"
                                    data-id="'.$row->id.'"
                                    type="checkbox" '.$checked.'>
                            </div>';
                })
                ->editColumn('created_at',
                    fn ($row) => $row->created_at->format('d-M, Y')
                )
                ->addColumn('actions', function ($row) {
                    $url = route('segmentation.show', $row->id);
                    return '<a href="'.$url.'" class="btn btn-sm btn-primary me-1">
                                <i class="far fa-eye"></i>
                            </a>';
                })
                ->rawColumns(['status', 'actions'])
                ->make(true);
        }

        return view('segmentation.index');
    }
    
    public function create()
    {
        $countriesStates = $this->countriesStates();
        return view('segmentation.create', compact('countriesStates'));
    }

    public function store(Request $request)
    {
        /* ---------- 1.  first-pass validation (field types) ----------------- */
        $baseRules = [
            'segment_name' => [
                'required', 'string', 'max:255',
                /* unique per domain */
                Rule::unique('segments', 'name')->where(fn ($q) =>
                    $q->where('domain', $request->domain_name)
                ),
            ],
            'domain_name'  => 'required|exists:domains,name',
            'segment_type' => 'required|in:device,geo',
        ];

        if ($request->segment_type === 'device') {
            $baseRules += [
                'devicetype'   => 'required|array|min:1|max:4',
                'devicetype.*' => 'in:desktop,tablet,mobile,other',
            ];
        }

        if ($request->segment_type === 'geo') {
            $rows = (array) $request->geo_type;   // could be [] on first load

            $baseRules += [
                'geo_type'      => 'required|array|min:1|max:5',
                'geo_type.*'    => 'in:equals,not_equals',
                'country'       => 'required|array|size:' . count($rows),
                'country.*'     => 'required|string|max:100',
                'state'         => 'array|size:' . count($rows),
                'state.*'       => 'nullable|string|max:100',
            ];
        }

        /* run first-pass validation */
        $data = Validator::make($request->all(), $baseRules)->validate();


        /* ---------- 2.  second-pass logical consistency --------------------- */
        $extraErrors = [];

        /* Device duplicates -------------------------------------------------- */
        if ($data['segment_type'] === 'device') {
            if (count($data['devicetype']) !== count(array_unique($data['devicetype']))) {
                $extraErrors['devicetype'] = 'Device types must be unique.';
            }
        }

        /* Geo consistency ---------------------------------------------------- */
        if ($data['segment_type'] === 'geo') {

            $seenCombo = [];      // key = country|state
            $opMatrix  = [];      // country|state => [equals=>bool, not_equals=>bool]

            //                                                          ─── cached map
            $validMap  = Cache::remember('psm:countries_states', 3600, function () {
                return PushSubscriptionMeta::selectRaw('DISTINCT country, state')
                    ->get()->groupBy('country')
                    ->map->pluck('state')->map->filter()->map->values()->toArray();
            });

            foreach ($data['geo_type'] as $i => $op) {
                $country = $data['country'][$i];
                $state   = $data['state'][$i] ?? '';
                $key     = $country . '|' . $state;

                /* duplicates */
                if (isset($seenCombo[$key])) {
                    $extraErrors['country.' . $i] = 'Duplicate country / state row.';
                    continue;
                }
                $seenCombo[$key] = true;

                /* conflicting op */
                $opMatrix[$key][$op] = true;
                if (!empty($opMatrix[$key]['equals']) && !empty($opMatrix[$key]['not_equals'])) {
                    $extraErrors['geo_type.' . $i] = 'Cannot mix "Only" and "Without" for the same location.';
                }

                /* unknown country/state */
                if (!isset($validMap[$country])) {
                    $extraErrors['country.' . $i] = 'Unknown country.';
                } elseif ($state && !in_array($state, $validMap[$country], true)) {
                    $extraErrors['state.' . $i] = 'State does not belong to selected country.';
                }
            }
        }

        if ($extraErrors) {
            throw ValidationException::withMessages($extraErrors);
        }

        /* ---------- 3.  save segment + rules in one TX ---------------------- */
        DB::transaction(function () use ($data) {

            $segment = Segment::create([
                'name'   => $data['segment_name'],
                'domain' => $data['domain_name'],
                'type'   => $data['segment_type'],
                'status' => true,
            ]);

            if ($data['segment_type'] === 'device') {
                foreach ($data['devicetype'] as $device) {
                    $segment->deviceRules()->create([
                        'device_type' => $device,
                    ]);
                }
                return;
            }

            foreach ($data['geo_type'] as $i => $op) {
                $segment->geoRules()->create([
                    'operator' => $op,
                    'country'  => $data['country'][$i],
                    'state'    => $data['state'][$i] ?? null,
                ]);
            }
        });

        return redirect()->route('segmentation.index')
                        ->with('success', 'Segment created successfully.');
    }

    public function remove($id)
    {
        $segment = Segment::findOrFail($id);
        $segment->update(['status' => ! $segment->status]);
        return response()->json(['success' => true, 'status' => $segment->status]);
    }

    private function countriesStates(): array
    {
        return Cache::remember('psm:countries_states', 3600, function () {
            return PushSubscriptionMeta::selectRaw('DISTINCT country, state')
                ->orderBy('country')->orderBy('state')
                ->get()
                ->groupBy('country')
                ->map(fn ($rows) => $rows->pluck('state')->filter()->unique()->values())
                ->toArray();
        });
    }

    // public function refreshData(Request $request)
    // {
    //     /* ---------- 1.  same field-level validation ------------------------ */
    //     $rules = [
    //         'segment_name' => 'required|string|max:255',
    //         'domain_name'  => 'required|exists:domains,name',
    //         'segment_type' => 'required|in:device,geo',
    //     ];

    //     if ($request->segment_type === 'device') {
    //         $rules += [
    //             'devicetype'   => 'required|array|min:1|max:4',
    //             'devicetype.*' => 'in:desktop,tablet,mobile,other',
    //         ];
    //     }

    //     if ($request->segment_type === 'geo') {
    //         $rows = count($request->geo_type ?? []);
    //         $rules += [
    //             'geo_type'      => 'required|array|min:1|max:5',
    //             'geo_type.*'    => 'in:equals,not_equals',
    //             'country'       => 'required|array|size:'.$rows,
    //             'country.*'     => 'required|string|max:100',
    //             'state'         => 'array|size:'.$rows,
    //             'state.*'       => 'nullable|string|max:100',
    //         ];
    //     }

    //     $data = $request->validate($rules);

    //     /* ---------- 2.  build a stable cache key --------------------------- */
    //     $cacheKey = 'seg:audience:' .
    //         md5(json_encode([
    //             $data['domain_name'],
    //             $data['segment_type'],
    //             $data['devicetype'] ?? [],
    //             $data['geo_type']   ?? [],
    //             $data['country']    ?? [],
    //             $data['state']      ?? [],
    //         ]));

    //     /* ---------- 3.  count with Cache::remember (120 s) ----------------- */
    //     $count = Cache::remember($cacheKey, 120, function () use ($data) {

    //         $q = DB::table('push_subscriptions_head as h')
    //             ->join('push_subscriptions_meta as m', 'm.head_id', '=', 'h.id')
    //             ->where('h.status', 1)
    //             ->where('h.domain', $data['domain_name']);

    //         /* device filter */
    //         if ($data['segment_type'] === 'device') {
    //             $q->whereIn('m.device', $data['devicetype']);
    //         }

    //         /* geo filter */
    //         if ($data['segment_type'] === 'geo') {
    //             $eq  = []; $neq = [];
    //             foreach ($data['geo_type'] as $i => $op) {
    //                 $row = [
    //                     'country' => $data['country'][$i],
    //                     'state'   => $data['state'][$i] ?? null,
    //                 ];

    //                 if ($op === 'equals') {
    //                     $eq[]  = $row;      // include list
    //                 } else {                // not_equals
    //                     $neq[] = $row;      // exclude list
    //                 }
    //             }
    //             if ($eq) {
    //                 $q->where(function ($sub) use ($eq) {
    //                     foreach ($eq as $row) {
    //                         $sub->orWhere(function ($w) use ($row) {
    //                             $w->where('m.country', $row['country']);
    //                             if ($row['state']) { $w->where('m.state', $row['state']); }
    //                         });
    //                     }
    //                 });
    //             }

    //             if ($neq) {
    //                 $q->whereNotExists(function ($sub) use ($neq) {
    //                     $sub->select(DB::raw(1))
    //                         ->from('push_subscriptions_meta as nx')
    //                         ->whereColumn('nx.head_id','h.id')
    //                         ->where(function ($inn) use ($neq) {
    //                             foreach ($neq as $row) {
    //                                 $inn->orWhere(function ($w) use ($row) {
    //                                     $w->where('nx.country', $row['country']);
    //                                     if ($row['state']) { $w->where('nx.state', $row['state']); }
    //                                 });
    //                             }
    //                         });
    //                 });
    //             }
    //         }

    //         return (int) $q->count();   // 🔢  DB does the heavy lifting
    //     });

    //     return response()->json(['count' => $count]);
    // }
    
    public function refreshData(Request $request)
    {
        /* ---------- 0.  Normalise input ----------------------------------- */
        if ($request->segment_type === 'device') {
            $request->merge([
                'devicetype' => collect($request->input('devicetype', []))
                                ->filter()
                                ->map('strtolower')
                                ->unique()
                                ->values()
                                ->toArray(),
            ]);
            $request->request->remove('geo_type');
            $request->request->remove('country');
            $request->request->remove('state');
        }

        /* ---------- 1.  Validate ------------------------------------------ */
        $rules = [
            'segment_name' => 'required|string|max:255',
            'domain_name'  => 'required|exists:domains,name',
            'segment_type' => 'required|in:device,geo',
        ];

        if ($request->segment_type === 'device') {
            $rules += [
                'devicetype'   => 'required|array|min:1|max:4',
                'devicetype.*' => 'in:desktop,tablet,mobile,other',
            ];
        }

        if ($request->segment_type === 'geo') {
            $rows = count($request->input('geo_type', []));
            $rules += [
                'geo_type'      => 'required|array|min:1|max:5',
                'geo_type.*'    => 'in:equals,not_equals',
                'country'       => 'required|array|size:'.$rows,
                'country.*'     => 'required|string|max:100',
                'state'         => 'array|size:'.$rows,
                'state.*'       => 'nullable|string|max:100',
            ];
        }

        $data = $request->validate($rules);

        /* ---------- 2.  Build single COUNT query -------------------------- */
        $q = DB::table('push_subscriptions_head as h')
            ->join('push_subscriptions_meta as m', 'm.head_id', '=', 'h.id')
            ->where('h.status', 1)
            ->where('h.domain', $data['domain_name']);

            /* Device filter */
            if ($data['segment_type'] === 'device') {
                $q->whereIn('m.device', $data['devicetype']);
            }

            /* Geo filter */
            if ($data['segment_type'] === 'geo') {

            /* split rows by operator */
            $equals    = [];
            $notEquals = [];

            foreach ($data['geo_type'] as $i => $op) {
                $row = [
                    'country' => $data['country'][$i],
                    'state'   => $data['state'][$i] ?? null,
                ];
                if ($op === 'equals')     { $equals[]    = $row; }
                else /* not_equals */     { $notEquals[] = $row; }
            }

            /* --- INCLUDE set (OR of all “Only …”) ---------------- */
            if ($equals) {
                $q->where(function ($sub) use ($equals) {
                    foreach ($equals as $row) {
                        $sub->orWhere(function ($w) use ($row) {
                            $w->where('m.country', $row['country']);
                            if ($row['state']) { $w->where('m.state', $row['state']); }
                        });
                    }
                });
            }

            /* --- EXCLUDE set (NOT EXISTS any “Without …”) -------- */
            if ($notEquals) {
                $q->whereNotExists(function ($sub) use ($notEquals) {
                    $sub->select(DB::raw(1))
                        ->from('push_subscriptions_meta as nx')
                        ->whereColumn('nx.head_id', 'h.id')
                        ->where(function ($inner) use ($notEquals) {
                            foreach ($notEquals as $row) {
                                $inner->orWhere(function ($w) use ($row) {
                                    $w->where('nx.country', $row['country']);
                                    if ($row['state']) { $w->where('nx.state', $row['state']); }
                                });
                            }
                        });
                });
            }
        }

        /* final scalar – zero memory impact */
        $count = (int) $q->count();

        return response()->json(['count' => $count]);
    }


}
