<?php

namespace Modules\NewsHub\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NewsHubController extends Controller
{
    public function index(Request $request)
    {
        // For filter dropdown (raw DB)
        $domains = DB::table('domains')
            ->select('name')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        if (! $request->ajax()) {
            return view('newshub::index', compact('domains'));
        }

        // Domains with News Roll + News Flask (raw joins)
        $query = DB::table('domains as d')
            ->leftJoin('news_rolls as nr', 'nr.domain_id', '=', 'd.id')
            ->leftJoin('news_flasks as nf', 'nf.domain_id', '=', 'd.id')
            ->select([
                'd.id as domain_id',
                'd.name as domain',

                'nr.id as nr_id',
                'nr.title as nr_title',
                'nr.status as nr_status',
                'nr.created_at as nr_created_at',

                'nf.id as nf_id',
                'nf.title as nf_title',
                'nf.status as nf_status',
                'nf.created_at as nf_created_at',
            ]);

        // Filters
        $query
            ->when($request->filled('status'), function ($q) use ($request) {
                $status = (int) $request->status;
                $q->where(function ($sub) use ($status) {
                    $sub->where('nr.status', $status)
                        ->orWhere('nf.status', $status);
                });
            })
            ->when($request->filled('search_term'), function ($q) use ($request) {
                $term = "%{$request->search_term}%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('d.name', 'like', $term)
                        ->orWhere('nr.title', 'like', $term)
                        ->orWhere('nf.title', 'like', $term);
                });
            })
            ->when($request->filled('site_web'),
                fn ($q) => $q->where('d.name', $request->site_web))
            ->when($request->filled('created_at'), function ($q) use ($request) {
                $dates = explode(' - ', $request->created_at);
                $from = Carbon::createFromFormat('m/d/Y', trim($dates[0]))->startOfDay();
                $to   = Carbon::createFromFormat('m/d/Y', trim($dates[1]))->endOfDay();
                $q->where(function ($sub) use ($from, $to) {
                    $sub->whereBetween('nr.created_at', [$from, $to])
                        ->orWhereBetween('nf.created_at', [$from, $to]);
                });
            });

        $query->orderBy('d.name', 'ASC');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('domain', function ($row) {
                return '<a href="https://' . e($row->domain) . '" target="_blank">' . e($row->domain) . '</a>';
            })
            ->addColumn('nr_title', fn ($row) => $row->nr_title ? e($row->nr_title) : '<span class="text-muted">---</span>')
            ->addColumn('nf_title', fn ($row) => $row->nf_title ? e($row->nf_title) : '<span class="text-muted">---</span>')
            ->addColumn('nr_status', function ($row) {
                $checked  = (int) $row->nr_status === 1 ? 'checked' : '';
                $disabled = is_null($row->nr_id) ? 'disabled' : '';
                return '
                    <label class="form-switch m-0">
                        <input type="checkbox" class="form-check-input js-toggle"
                               data-type="roll"
                               data-id="'.e($row->nr_id).'"
                               '.$checked.' '.$disabled.'>
                    </label>
                ';
            })
            ->addColumn('nf_status', function ($row) {
                $checked  = (int) $row->nf_status === 1 ? 'checked' : '';
                $disabled = is_null($row->nf_id) ? 'disabled' : '';
                return '
                    <label class="form-switch m-0">
                        <input type="checkbox" class="form-check-input js-toggle"
                               data-type="flask"
                               data-id="'.e($row->nf_id).'"
                               '.$checked.' '.$disabled.'>
                    </label>
                ';
            })
            ->addColumn('nr_action', function ($row) {
                $rollUrl = encryptUrl(route('news-hub.roll'), ['domain' => $row->domain]);
                if ($row->nr_id) {
                    return '<a href="'.e($rollUrl).'" class="btn btn-secondary btn-sm" title="Edit News Roll"><i class="fas fa-edit"></i></a>';
                }
                return '<a href="'.e($rollUrl).'" class="btn btn-primary btn-sm" title="Setup News Roll"><i class="fas fa-plus"></i></a>';
            })
            ->addColumn('nf_action', function ($row) {
                $flaskUrl = encryptUrl(route('news-hub.flask'), ['domain' => $row->domain]);
                if ($row->nf_id) {
                    return '<a href="'.e($flaskUrl).'" class="btn btn-secondary btn-sm" title="Edit News Flask"><i class="fas fa-edit"></i></a>';
                }
                return '<a href="'.e($flaskUrl).'" class="btn btn-primary btn-sm" title="Setup News Flask"><i class="fas fa-plus"></i></a>';
            })
            ->rawColumns(['domain', 'nr_title', 'nf_title', 'nr_status', 'nf_status', 'nr_action', 'nf_action'])
            ->make(true);
    }

    /**
     * Unified toggle endpoint for both components (raw DB, fast).
     * Expects: type=roll|flask, id, status=0|1
     */
    public function toggleStatus(Request $request)
    {
        $request->validate([
            'type'   => 'required|in:roll,flask',
            'id'     => 'required|integer|min:1',
            'status' => 'required|in:0,1',
        ]);

        $table = $request->type === 'roll' ? 'news_rolls' : 'news_flasks';

        $updated = DB::table($table)
            ->where('id', (int) $request->id)
            ->update(['status' => (int) $request->status]);

        if (!$updated) {
            return response()->json(['ok' => false, 'message' => 'Record not found'], 404);
        }

        return response()->json(['ok' => true]);
    }

    public function roll(Request $request)
    {
        try {
            $request->validate(['eq' => 'required|string']);
            $payload = decryptUrl($request->eq);
            $domainName = $payload['domain'] ?? null;

            if (!$domainName) {
                return redirect()->route('domain.view')->with('error', 'Invalid link.');
            }

            $domain = DB::table('domains')
                ->select('id', 'name', 'status')
                ->where('name', $domainName)
                ->first();

            if (!$domain || (int) $domain->status !== 1) {
                return redirect()->route('domain.view')->with('error', 'Domain not found or inactive.');
            }

            $roll = DB::table('news_rolls')
                ->where('domain_id', $domain->id)
                ->first();

            // pass eq to the view so saves/fetches use it
            return view('newshub::roll', [
                'domain' => $domain,
                'roll'   => $roll,
                'eq'     => $request->eq,
            ]);
        } catch (\Throwable $th) {
            return redirect()->route('domain.view')->with('error', 'Invalid or expired link.');
        }
    }

    public function saveRoll(Request $request)
    {
        // 1) Validate user inputs (note: user supplies only the PATH, not full URL)
        $validated = $request->validate([
            'eq'                => ['required', 'string'],
            'feed_path'         => ['required', 'string', 'max:512', 'regex:/^\/[^\s]*$/'], // must start with /
            'title'             => ['required', 'string', 'max:190'],
            'icon'              => ['nullable', 'string', 'max:190'],
            'widget_placement'  => ['required', 'in:top-left,top-right,bottom-left,bottom-right'],
            'theme_color'       => ['required','regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'show_on_desktop'   => ['nullable','boolean'],
            'show_on_mobile'    => ['nullable','boolean'],
        ]);

        // 2) Resolve domain from eq (don’t trust form for domain_id)
        $payload = decryptUrl($validated['eq']);
        $domainName = $payload['domain'] ?? null;

        if (!$domainName) {
            return back()->with('error', 'Invalid link.')->withInput();
        }

        $domain = DB::table('domains')
            ->select('id', 'status')
            ->where('name', $domainName)
            ->first();

        if (!$domain || (int) $domain->status !== 1) {
            return back()->with('error', 'Domain not found or inactive.')->withInput();
        }

        // 3) Build the FULL feed URL from fixed base + path
        $BASE = 'https://domain.in';
        $path = '/' . ltrim($validated['feed_path'], '/'); // normalize
        $fullFeedUrl = rtrim($BASE, '/') . $path;

        // 4) Cast the switches (unchecked checkboxes don’t arrive)
        $showOnDesktop = $request->boolean('show_on_desktop');
        $showOnMobile  = $request->boolean('show_on_mobile');

        // 5) Upsert by domain_id (works for both create/edit)
        DB::table('news_rolls')->updateOrInsert(
            ['domain_id' => (int) $domain->id],
            [
                'feed_url'          => $fullFeedUrl,
                'title'             => $validated['title'],
                'icon'              => $validated['icon'] ?: 'fa fa-bell',
                'widget_placement'  => $validated['widget_placement'],
                'theme_color'       => $validated['theme_color'],
                'show_on_desktop'   => $showOnDesktop ? 1 : 0,
                'show_on_mobile'    => $showOnMobile ? 1 : 0,
                'updated_at'        => now(),
                'created_at'        => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );

        return back()->with('success', 'News Roll saved successfully.');
    }

    public function fetchFeed(Request $request): array
    {
        try {
            // user provides only PATH; we add the fixed base. Also require eq to gate by domain.
            $request->validate([
                'eq'        => 'required|string',
                'feed_path' => ['required','string','max:512','regex:/^\/[^\s]*$/'],
                'feed_type' => 'nullable|in:random,all',
                'random_feed' => 'nullable|integer|min:1|max:50',
            ]);

            $payload = decryptUrl($request->eq);
            $domainName = $payload['domain'] ?? null;
            if (!$domainName) {
                return ['status' => false, 'message' => 'Invalid link.'];
            }

            // Optionally confirm target domain is active (authorization gate)
            $domain = DB::table('domains')->where('name', $domainName)->where('status', 1)->first();
            if (!$domain) {
                return ['status' => false, 'message' => 'Domain not found or inactive.'];
            }

            // Build full URL from BASE + path (ignore any scheme/user input attempts)
            $BASE = 'https://domain.in';
            $path = '/' . ltrim($request->input('feed_path'), '/');
            $url  = rtrim($BASE, '/') . $path;

            // cURL fetch (same as before)
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            ]);

            $body   = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error  = curl_error($ch);
            curl_close($ch);

            if ($status !== 200) {
                return ['status' => false, 'message' => "Could not fetch feed (HTTP {$status})", 'error' => $error];
            }

            $body = preg_replace('/^\x{FEFF}/u', '', (string) $body);
            $body = trim($body);

            $encoding = mb_detect_encoding($body, ['UTF-8', 'ISO-8859-1', 'WINDOWS-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $body = mb_convert_encoding($body, 'UTF-8', $encoding);
            }

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($xml === false) {
                $errors = array_map(function($e) {
                    return trim($e->message) . " (Line {$e->line})";
                }, libxml_get_errors());
                libxml_clear_errors();
                return ['status' => false, 'message' => 'Invalid RSS Feed Format', 'error' => 'Invalid XML: ' . implode('; ', $errors)];
            }

            // Accept <rss><channel> OR Atom (<feed>)
            $items = [];
            $maxItems = 50;

            if (isset($xml->channel)) {
                $count = 0;
                foreach ($xml->channel->item as $item) {
                    if ($count++ >= $maxItems) break;
                    $items[] = $this->mapRssItem($item);
                }
            } elseif ($xml->getName() === 'feed') { // Atom
                $count = 0;
                foreach ($xml->entry as $entry) {
                    if ($count++ >= $maxItems) break;
                    $items[] = $this->mapAtomEntry($entry);
                }
            } else {
                return ['status' => false, 'message' => 'The URL does not appear to be a valid RSS/Atom feed', 'error' => 'Unsupported XML root'];
            }

            // Randomization if requested
            if ($request->input('feed_type') === 'random' && !empty($items)) {
                $randomCount = min((int) $request->input('random_feed', 2), count($items));
                shuffle($items);
                $items = array_slice($items, 0, $randomCount);
            }

            return ['status' => true, 'items' => $items, 'count' => count($items)];

        } catch (\Exception $e) {
            Log::error('RSS Fetch Error: ' . $e->getMessage());
            return ['status' => false, 'message' => 'An error occurred while fetching the feed: ' . $e->getMessage()];
        }
    }

    private function mapRssItem(\SimpleXMLElement $item): array
    {
        $title = trim((string) $item->title);
        $link  = trim((string) $item->link);
        $description = trim((string) $item->description);

        // clean desc
        $cleanDesc = strip_tags($description);
        $cleanDesc = preg_replace('/\s+/', ' ', $cleanDesc);
        $cleanDesc = trim($cleanDesc);

        // image extraction
        $image = '';
        $media = $item->children('media', true);
        if (isset($media->content)) {
            $image = (string) $media->content->attributes()->url;
        } elseif (isset($media->thumbnail)) {
            $image = (string) $media->thumbnail->attributes()->url;
        } elseif (isset($item->enclosure) && strpos((string) $item->enclosure['type'], 'image/') === 0) {
            $image = (string) $item->enclosure['url'];
        } elseif (preg_match('/<img\s+[^>]*src="([^"]+)"/i', (string) $item->description, $m)) {
            $image = $m[1];
        }

        if ($image && !filter_var($image, FILTER_VALIDATE_URL)) {
            $image = '';
        }
        if (!$image) {
            if ($link && ($host = parse_url($link, PHP_URL_HOST))) {
                $image = 'https://www.google.com/s2/favicons?domain=' . $host;
            }
        }

        return [
            'title'       => $title,
            'link'        => $link,
            'description' => $cleanDesc,
            'image'       => $image,
        ];
    }

    private function mapAtomEntry(\SimpleXMLElement $entry): array
    {
        $title = trim((string) $entry->title);
        $link  = '';
        foreach ($entry->link as $lnk) {
            $rel = (string) $lnk['rel'];
            if ($rel === 'alternate' || $rel === '') {
                $link = (string) $lnk['href'];
                break;
            }
        }
        $summary = (string) ($entry->summary ?? $entry->content ?? '');
        $cleanDesc = trim(preg_replace('/\s+/', ' ', strip_tags($summary)));

        // Atom rarely carries image in standard place; fallback to favicon if we have a link
        $image = '';
        if ($link && ($host = parse_url($link, PHP_URL_HOST))) {
            $image = 'https://www.google.com/s2/favicons?domain=' . $host;
        }

        return [
            'title'       => $title,
            'link'        => $link,
            'description' => $cleanDesc,
            'image'       => $image,
        ];
    }

    /**
     * NEWS FLASK (popup) setup/edit page (raw DB)
    */
    public function flask(Request $request)
    {
        try {
            $request->validate(['eq' => 'required|string']);
            $payload = decryptUrl($request->eq);
            $domainName = $payload['domain'] ?? null;

            if (!$domainName) {
                return redirect()->route('domain.view')->with('error', 'Invalid link.');
            }

            $domain = DB::table('domains')
                ->select('id', 'name', 'status')
                ->where('name', $domainName)
                ->first();

            if (!$domain || (int)$domain->status !== 1) {
                return redirect()->route('domain.view')->with('error', 'Domain not found or inactive.');
            }

            $flask = DB::table('news_flasks')
                ->where('domain_id', $domain->id)
                ->first();

            return view('newshub::flask', compact('domain', 'flask'));

        } catch (\Throwable $th) {
            return redirect()->route('domain.view')->with('error', 'Invalid or expired link.');
        }
    }

    public function saveFlask(Request $request)
    {
        $v = Validator::make($request->all(), [
            'domain_id'                 => ['required','integer','exists:domains,id'],
            'feed_url'                  => ['required','url','max:2048'],
            'title'                     => ['required','string','max:255'],
            'theme_color'               => ['required','regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'trigger_timing'            => ['required', Rule::in(['exit_intent','after_seconds','after_scroll'])],
            'after_seconds'             => ['nullable','integer','min:1','max:86400'], // only used if after_seconds
            'show_again_after_minutes'  => ['required','integer','min:0','max:10080'],
            'enable_desktop'            => ['nullable','in:on,1,0'],
            'enable_mobile'             => ['nullable','in:on,1,0'],
            'status'                    => ['nullable','in:on,1,0'],
        ], [
            'theme_color.regex' => 'Theme color must be a valid hex like #fd683e',
        ]);

        $v->validate();

        $trigger = $request->trigger_timing;
        $afterSeconds = $trigger === 'after_seconds' ? (int) $request->after_seconds : null;

        $payload = [
            'domain_id'                 => (int) $request->domain_id,
            'feed_url'                  => $request->feed_url,
            'title'                     => $request->title,
            'theme_color'               => $request->theme_color,
            'exit_intent'               => $trigger === 'exit_intent',
            'after_seconds'             => $afterSeconds,
            'scroll_down'               => $trigger === 'after_scroll',
            'show_again_after_minutes'  => (int) $request->show_again_after_minutes,
            'enable_desktop'            => $request->boolean('enable_desktop'),
            'enable_mobile'             => $request->boolean('enable_mobile'),
            'status'                    => $request->boolean('status'),
            'created_at'                => now(),
            'updated_at'                => now(),
        ];

        DB::table('news_flasks')->upsert(
            [$payload],
            ['domain_id'],
            ['feed_url','title','theme_color','exit_intent','after_seconds','scroll_down','show_again_after_minutes','enable_desktop','enable_mobile','status','updated_at']
        );

        return redirect()->route('news-hub.index')->with('success', 'News Flask saved.');
    }

    public function newsHub(Request $request){
        // USE CACHE AND AS WELL...
        $jsContent = view('newshub::script')->render();

        return response($jsContent, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

}
