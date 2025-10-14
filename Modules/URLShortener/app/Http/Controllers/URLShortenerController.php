<?php

namespace Modules\URLShortener\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\URLShortener\Models\UrlShorter;
use Illuminate\Support\Str; 
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Cache;
use App\Models\PushConfig;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;

class URLShortenerController extends Controller
{
    public function index()
    {
        return view('urlshortener::index');
    }

    public function youtube(Request $request)
    {
        if ($request->ajax()) {
            // 1) Aggregate subscribers by normalized domain (strip leading '@'), status=1
            $subsAgg = DB::table('push_subscriptions_head as p')
                ->select([
                    DB::raw("TRIM(LEADING '@' FROM p.domain) as handle"),   // normalize @Netflix -> Netflix
                    DB::raw('COUNT(*) as total_subscribers')
                ])
                ->where('p.status', 1)
                ->groupBy('handle');

            // 2) Extract channel handle from url_shorter.target_url ONCE (without '@')
            $urlsWithHandle = DB::table('url_shorter as u')
                ->where('u.type', 'yt')
                ->select([
                    'u.id',
                    'u.target_url',
                    'u.short_url',
                    'u.created_at',
                    'u.status',
                    DB::raw("
                        SUBSTRING_INDEX(
                            SUBSTRING_INDEX(SUBSTRING_INDEX(u.target_url, '?', 1), '/@', -1),
                            '/', 1
                        ) as handle
                    ")
                ]);

            // 3) Join derived tables on normalized handle
            $query = DB::query()
                ->fromSub($urlsWithHandle, 'u')
                ->leftJoinSub($subsAgg, 'psh', function ($join) {
                    $join->on('u.handle', '=', 'psh.handle');
                })
                ->select([
                    'u.id',
                    'u.target_url',
                    'u.short_url',
                    'u.created_at',
                    'u.status',
                    DB::raw('COALESCE(psh.total_subscribers, 0) as total_subscribers'),
                ]);

            // --- Filters/search ---
            if ($request->filled('search_term')) {
                $term = trim($request->search_term);
                $cleanHandle = ltrim($term, '@');
                $query->where(function ($q) use ($term, $cleanHandle) {
                    $q->where('u.target_url', 'like', '%' . $term . '%')
                    ->orWhere('u.target_url', 'like', '%@' . $cleanHandle . '%')
                    ->orWhere('u.short_url', 'like', '%' . $term . '%')
                    ->orWhere('u.handle', 'like', '%' . $cleanHandle . '%');
                });
            }

            if ($request->filled('channel_list')) {
                $selected = ltrim($request->channel_list, '@');
                $query->where('u.handle', $selected);
            }

            if ($request->filled('filter_status')) {
                $query->where('u.status', (int) $request->filter_status);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('target_url', function ($row) {
                    $channelName = preg_replace('/^https?:\/\/(www\.)?youtube\.com\/@/i', '', $row->target_url);
                    $channelName = preg_replace('#/.*$#', '', $channelName);
                    $channelAnchor = '<a href="' . e($row->target_url) . '" target="_blank">@' . e($channelName) . '</a>';
                    $copyBtn = '<button type="button" class="btn btn-link p-0 ms-2 copy-url" data-url="' . e($row->target_url) . '" title="Copy channel URL"><i class="far fa-copy"></i></button>';
                    return $channelAnchor . $copyBtn;
                })
                ->editColumn('short_url', function ($row) {
                    $shortLink = route('api.shorturl.subs', ['type' => 'yt', 'code' => $row->short_url]);
                    $shortAnchor = '<a href="' . e($shortLink) . '" target="_blank">' . e($shortLink) . '</a>';
                    $copyBtn = '<button type="button" class="btn btn-link p-0 ms-2 copy-url" data-url="' . e($shortLink) . '" title="Copy short URL"><i class="far fa-copy"></i></button>';
                    return $shortAnchor . $copyBtn;
                })
                ->editColumn('created_at', fn($row) => \Carbon\Carbon::parse($row->created_at)->format('d-M, Y'))
                ->addColumn('total_subscribers', fn($row) => (int) $row->total_subscribers)
                ->addColumn('status', function ($row) {
                    $checked = $row->status == 1 ? 'checked' : '';
                    return '<div class="form-check form-switch">
                                <input class="form-check-input status_input" type="checkbox" role="switch" ' . $checked . ' data-id="' . e($row->id) . '">
                            </div>';
                })
                ->addColumn('actions', fn($row) =>
                    '<button type="button" class="btn btn-sm btn-danger" onclick="deleteUrl(' . e($row->id) . ')" title="Delete"><i class="fas fa-trash"></i></button>'
                )
                ->rawColumns(['target_url', 'short_url', 'status', 'actions'])
                ->make(true);
        }

        return view('urlshortener::youtube');
    }

    public function youtubeList(Request $request)
    {
        if ($request->ajax()) {
            // Fetch unique YouTube channel names (@handle part after '@')
            $channels = UrlShorter::where('type', 'yt')
                ->selectRaw('DISTINCT SUBSTRING_INDEX(target_url, "@", -1) AS channel_name')
                ->pluck('channel_name');

            return response()->json($channels);
        }
    }

    public function youtubeStatus(Request $request)
    {
       try {
            $request->validate([
                'id'     => 'required|integer|exists:url_shorter,id',
                'status' => 'required|boolean',
            ]);

            $row = UrlShorter::whereIn('type',[ 'yt','url'])->findOrFail($request->id);
            $row->status = (int) $request->status;
            $row->save();

            return response()->json([
                'success' => true,
                'message' => $row->status ? 'Short link activated.' : 'Short link deactivated.',
            ]);
       } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. ' . $th->getMessage(),
            ]);
       }
    }

    public function deleteYoutube($id)
    {
        try {
            $row = UrlShorter::whereIn('type',[ 'yt','url'])->findOrFail($id);
            $row->delete();

            return response()->json([
                'success' => true,
                'message' => 'Short Link removed successfully.',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.' . $th->getMessage(),
            ]);
        }
    }

    public function createYoutube()
    {
        return view('urlshortener::create_youtube');
    }

    public function youtubeStore(Request $request)
    {
        try {
            // Validate the incoming request
            $validated = $request->validate([
                'channel_url' => 'required|url|regex:/^https?:\/\/(www\.)?youtube\.com\/@[\w.\-]+(\/.*)?$/',
                'prompt' => 'required|string|max:200',
                'forced_subscribe' => 'nullable|boolean', // Allow null or boolean
            ]);

            // Default the 'forced_subscribe' value to false if it's not set in the request
            $forcedSubscribe = $validated['forced_subscribe'] ?? false;

            // Generate a unique code for the short URL
            $uniqueCode = Str::random(10);  // Prefix 'yt' and create a unique code

            // Check if the short_url already exists in the database
            $shortUrlExists = UrlShorter::where('short_url', $uniqueCode)->exists();

            // If it exists, generate a new unique code
            while ($shortUrlExists) {
                $uniqueCode = Str::random(10); // Generate a new 10-character string
                $shortUrlExists = UrlShorter::where('short_url', $uniqueCode)->exists();
            }

            // Generate the short URL
            // $shortUrl = route('api.shorturl.subs', ['type' => 'yt', 'code' => $uniqueCode]);

            // Ensure target_url is unique
            $urlExists = UrlShorter::where('target_url', $validated['channel_url'])->exists();
            if ($urlExists) {
                return back()->with('error', 'This YouTube URL is already shortened.');
            }

            // Save the data to the database
            $urlShorter = UrlShorter::create([
                'target_url' => $validated['channel_url'],
                'short_url' => $uniqueCode,
                'prompt' => $validated['prompt'],
                'type' => 'yt',  // type is 'yt' for YouTube
                'forced_subscribe' => $forcedSubscribe, // Ensure 'forced_subscribe' is handled
                'status' => 1,  // Set status to active or as required
            ]);

            return redirect()->route('url_shortener.youtube')->with('success', 'YouTube URL shortened successfully!');
        } catch (\Throwable $th) {
            return back()->with('error', 'An error occurred. Please try again later.');
        }
    }

    public function shorturlSubs($type, $code)
    {
        try {
            // include type for downstream YouTube-domain logic later
            $urlEntry = UrlShorter::select('short_url', 'status', 'target_url', 'type')
                ->where('type', $type)
                ->where('short_url', $code)
                ->firstOrFail();

            if ((int) $urlEntry->status === 1) {
                // NOTE: you chose this route name; make sure it exists in your routes.
                $url   = route('api.push.permission');
                $param = ['code' => $urlEntry->short_url];
                $encryptUrl = encryptUrl($url, $param); // uses your project helper

                return redirect($encryptUrl);
            }

            // Inactive: go straight to target
            return redirect($urlEntry->target_url);
        } catch (\Throwable $th) {
            return redirect('https://www.google.com/');
        }
    }

    public function pushPermission(Request $request)
    {
        try {
            $request->validate([
                'eq' => 'required|string',
            ]);

            $data = decryptUrl($request->input('eq'));

            $urlEntry = UrlShorter::select('short_url','domain','target_url', 'prompt', 'forced_subscribe', 'type')
                ->where('status', 1)
                ->where('short_url', $data['code'] ?? null)
                ->firstOrFail();

            // cache push config for 24 hours
            $config = Cache::remember('push_permission', now()->addDay(), function () {
                return PushConfig::firstOrFail();
            });

            if($urlEntry->type === 'yt'){
                $domainForApi = parse_url($urlEntry->target_url ?? '', PHP_URL_HOST) ?: $request->getHost();
                if ($urlEntry->type === 'yt' && is_string($urlEntry->target_url)) {
                    if (preg_match('/@([A-Za-z0-9._-]+)/', $urlEntry->target_url, $m)) {
                        $domainForApi = '@' . $m[1];
                    }
                }
            }else{
                $domainForApi = $urlEntry->short_url;
            }

            $defaultParent = $urlEntry->domain ?? 'default.com';

            return view('urlshortener::subscribe_prompt', [
                'cfg'               => $config->web_app_config,
                'vapid'             => $config->vapid_public_key,
                'data'              => $urlEntry,
                'serviceWorkerPath' => '/apluselfhost-messaging-sw.js',
                'subscribeUrl'      => route('api.subscribe'),
                'defaultParent'     => $defaultParent, 
                'domainForApi'      => $domainForApi,
            ]);

        } catch (\Throwable $th) {
            return redirect('https://www.google.com/');
        }
    }

    // FOR LINKS
    public function link(Request $request)
    {
        if ($request->ajax()) {

            // Filter early to shrink LEFT side before join
            $base = DB::table('url_shorter as u')->where('u.type', 'url');

            if ($request->filled('search_term')) {
                $term = trim($request->search_term);
                $base->where(function ($q) use ($term) {
                    $q->where('u.target_url', 'like', "%{$term}%")
                    ->orWhere('u.short_url',  'like', "%{$term}%");
                });
            }

            if ($request->filled('filter_status')) {
                $base->where('u.status', (int) $request->filter_status);
            }

            // Simple LEFT JOIN + COUNT — nothing fancy
            $query = $base
                ->leftJoin('push_subscriptions_head as p', function ($join) {
                    $join->on('p.domain', '=', 'u.short_url')
                        ->where('p.status', 1);
                })
                ->groupBy('u.id') // ensures one row per short link
                ->select([
                    'u.id',
                    DB::raw('MAX(u.target_url)  as target_url'),
                    DB::raw('MAX(u.short_url)   as short_url'),
                    DB::raw('MAX(u.status)      as status'),
                    DB::raw('MAX(u.created_at)  as created_at'),
                    DB::raw("DATE_FORMAT(MAX(u.created_at), '%d-%b, %Y') as created_at_fmt"),
                    DB::raw('COUNT(p.id)        as total_subscribers'),
                ]);

            return \DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('target_url', function ($row) {
                    $a = '<a href="'.e($row->target_url).'" target="_blank">'.e($row->target_url).'</a>';
                    $copy = '<button type="button" class="btn btn-link p-0 ms-2 copy-url" data-url="'.e($row->target_url).'" title="Copy URL"><i class="far fa-copy"></i></button>';
                    return $a . $copy;
                })
                ->editColumn('short_url', function ($row) {
                    $shortLink = route('api.shorturl.subs', ['type' => 'url', 'code' => $row->short_url]);
                    $a = '<a href="'.e($shortLink).'" target="_blank">'.e($shortLink).'</a>';
                    $copy = '<button type="button" class="btn btn-link p-0 ms-2 copy-url" data-url="'.e($shortLink).'" title="Copy short URL"><i class="far fa-copy"></i></button>';
                    return $a . $copy;
                })
                ->editColumn('created_at', fn($row) => $row->created_at_fmt)
                ->addColumn('total_subscribers', fn($row) => (int) $row->total_subscribers)
                ->addColumn('status', function ($row) {
                    $checked = ((int)$row->status === 1) ? 'checked' : '';
                    return '<div class="form-check form-switch">
                            <input class="form-check-input status_input" type="checkbox" role="switch" '.$checked.' data-id="'.e($row->id).'">
                            </div>';
                })
                ->addColumn('actions', fn($row) =>
                    '<button type="button" class="btn btn-sm btn-danger" onclick="deleteUrl('.e($row->id).')" title="Delete"><i class="fas fa-trash"></i></button>'
                )
                ->rawColumns(['target_url','short_url','status','actions'])
                ->make(true);
        }

        return view('urlshortener::link');
    }

    public function createLink()
    {
        return view('urlshortener::create_link');
    }

    public function linkStore(Request $request)
    {
        try {
            // Validate the incoming request
            $validated = $request->validate([
                'url'              => 'required|url|max:2048',
                'prompt'           => 'required|string|max:200',
                'forced_subscribe' => 'nullable|boolean',
                'domain'           => 'required|string|max:255',
            ]);

            // Normalize/cast checkbox
            $forcedSubscribe = (bool)($validated['forced_subscribe'] ?? false);

            // Ensure target_url is unique
            $urlExists = UrlShorter::where('target_url', $validated['url'])->exists();
            if ($urlExists) {
                return back()->withInput()->with('error', 'This URL is already shortened.');
            }

            // Generate a unique code for the short URL
            $uniqueCode = Str::random(10);
            while (UrlShorter::where('short_url', $uniqueCode)->exists()) {
                $uniqueCode = Str::random(10);
            }

            // Save
            UrlShorter::create([
                'domain'           => $validated['domain'],     // save selected domain
                'target_url'       => $validated['url'],        // correct field
                'short_url'        => $uniqueCode,
                'prompt'           => $validated['prompt'],
                'type'             => 'url',
                'forced_subscribe' => $forcedSubscribe ? 1 : 0,
                'status'           => 1,
            ]);

            return redirect()
                ->route('url_shortener.link')
                ->with('success', 'URL shortened successfully!');
        } catch (\Throwable $th) {
            return back()->withInput()->with('error', 'An error occurred. Please try again later.');
        }
    }
}
