<?php

namespace Modules\NewsHub\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use App\Models\Domain;

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
            ->leftJoin('news_bottom_sliders as nbs', 'nbs.domain_id', '=', 'd.id')
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

                'nbs.id as nbs_id',
                'nbs.status as nbs_status',
                'nbs.created_at as nbs_created_at',
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
            // ->addColumn('nr_title', fn ($row) => $row->nr_title ? e($row->nr_title) : '<span class="text-muted">---</span>')
            // ->addColumn('nf_title', fn ($row) => $row->nf_title ? e($row->nf_title) : '<span class="text-muted">---</span>')
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
            ->addColumn('nbs_status', function ($row) {
                $checked  = (int) $row->nbs_status === 1 ? 'checked' : '';
                $disabled = is_null($row->nbs_id) ? 'disabled' : '';
                return '
                    <label class="form-switch m-0">
                        <input type="checkbox" class="form-check-input js-toggle"
                               data-type="slider"
                               data-id="'.e($row->nbs_id).'"
                               '.$checked.' '.$disabled.'>
                    </label>
                ';
            })
            ->addColumn('nbs_action', function ($row) {
                $sliderUrl = encryptUrl(route('news-hub.bottom-slider'), ['domain' => $row->domain]);
                if ($row->nbs_id) {
                    return '<a href="'.e($sliderUrl).'" class="btn btn-secondary btn-sm" title="Edit News Slider"><i class="fas fa-edit"></i></a>';
                }
                return '<a href="'.e($sliderUrl).'" class="btn btn-primary btn-sm" title="Setup News Slider"><i class="fas fa-plus"></i></a>';
            })
            ->addColumn('integrate', function ($row) {
                $url = route('api.push.news-hub')."?site=".e($row->domain);
                $script = '<script async src="'.e($url).'"></script>';

                if ($row->nr_id || $row->nf_id || $row->nbs_id) {
                    return '<button class="btn btn-info btn-sm js-copy" data-clipboard-text="'.e($script).'" title="Copy Integration Code" data-bs-toggle="modal" data-bs-target="#openIntegrationModal"><i class="fas fa-code"></i></button>';
                }
                else {
                    return '<button class="btn btn-danger btn-sm" title="Please setup at least one component"><i class="fas fa-ban"></i></button>';
                }
            })
            ->rawColumns(['domain', 'nr_title', 'nf_title','nr_status', 'nf_status', 'nr_action', 'nf_action','nbs_status', 'nbs_action','integrate'])
            ->make(true);
    }

    /**
     * Unified toggle endpoint for both components (raw DB, fast).
     * Expects: type=roll|flask, id, status=0|1
     */
    public function toggleStatus(Request $request)
    {
        $request->validate([
            'type'   => 'required|in:roll,flask,slider',
            'id'     => 'required|integer|min:1',
            'status' => 'required|in:0,1',
        ]);

        $table = $request->type === 'roll' ? 'news_rolls' : ($request->type === 'flask' ? 'news_flasks' : 'news_bottom_sliders');

        $updated = DB::table($table)
            ->where('id', (int) $request->id)
            ->update(['status' => (int) $request->status]);

        if (!$updated) {
            return response()->json(['ok' => false, 'message' => 'Record not found'], 404);
        }

        // clear cache 
        if($updated) {
            $domain = DB::table('domains')->where('id', (int) $request->id)->value('name');
            if($domain) {
                Cache::forget("apsh_cfg_{$domain}");
            }
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

            // Single query: domain + (optional) roll
            $row = DB::table('domains as d')
                ->leftJoin('news_rolls as r', 'r.domain_id', '=', 'd.id')
                ->where('d.name', $domainName)
                ->where('d.status', 1)
                ->select(
                    'd.id as domain_id',
                    'd.name as domain_name',
                    'd.status as domain_status',
                    'r.feed_url',
                    'r.title',
                    'r.icon',
                    'r.widget_placement',
                    'r.theme_color',
                    'r.show_on_desktop',
                    'r.show_on_mobile'
                )
                ->first();

            if (!$row) {
                return redirect()->route('domain.view')->with('error', 'Domain not found or inactive.');
            }

            // Normalize to the same variables your Blade expects
            $domain = (object)[
                'id'     => $row->domain_id,
                'name'   => $row->domain_name,
                'status' => $row->domain_status,
            ];

            $roll = $row->feed_url ? (object)[
                'feed_url'         => $row->feed_url,
                'title'            => $row->title,
                'icon'             => $row->icon,
                'widget_placement' => $row->widget_placement,
                'theme_color'      => $row->theme_color,
                'show_on_desktop'  => $row->show_on_desktop,
                'show_on_mobile'   => $row->show_on_mobile,
            ] : null;

            return view('newshub::roll', [
                'domain' => $domain,
                'roll'   => $roll,
                'eq'     => $request->eq,
            ]);
        } catch (\Throwable $th) {
            return redirect()->route('domain.view')->with('error', 'Invalid or expired link.');
        }
    }

    public function rollSave(Request $request)
    {
        try {
            // Validate (only bottom-left/right now)
            $validated = $request->validate([
                'eq'               => ['required','string'],
                'feed_path'        => ['required','string','max:512','regex:/^\/[^\s]*$/'],
                'title'            => ['required','string','max:190'],
                'icon'             => ['nullable','string','max:190'],
                'widget_placement' => ['required','in:bottom-left,bottom-right'],
                'theme_color'      => ['required','regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            ]);

            // Resolve domain from eq
            $payload    = decryptUrl($validated['eq']);
            $domainName = $payload['domain'] ?? null;
            if (!$domainName) {
                return back()->with('error','Invalid link.')->withInput();
            }

            $domain = DB::table('domains')
                ->select('id','status')
                ->where('name',$domainName)
                ->first();

            if (!$domain || (int)$domain->status !== 1) {
                return back()->with('error','Domain not found or inactive.')->withInput();
            }

            // Build FULL URL from decrypted domain + path
            $path        = '/' . ltrim($validated['feed_path'], '/');
            $fullFeedUrl = 'https://' . rtrim($domainName, '/') . $path;

            // Handle switches reliably: "on" => 1, missing => 0
            $showOnDesktop = $request->has('show_on_desktop') ? 1 : 0;
            $showOnMobile  = $request->has('show_on_mobile')  ? 1 : 0;

            DB::table('news_rolls')->updateOrInsert(
                ['domain_id' => (int)$domain->id],
                [
                    'feed_url'         => $fullFeedUrl,
                    'title'            => $validated['title'],
                    'icon'             => $validated['icon'] ?: 'fa fa-bell',
                    'widget_placement' => $validated['widget_placement'],
                    'theme_color'      => $validated['theme_color'],
                    'show_on_desktop'  => $showOnDesktop,
                    'show_on_mobile'   => $showOnMobile,
                    'updated_at'       => now(),
                    'created_at'       => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
            
            // clear cache 
            if($domainName) {
                Cache::forget("apsh_cfg_{$domainName}");
            }

            return redirect()->route('news-hub.index')->with('success','News Roll saved successfully.');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error','Error : '.$th->getMessage());
        }
    }

    public function fetchFeed(Request $request): array
    {
        try {
            $request->validate([
                'eq'          => 'required|string',
                'feed_path'   => ['required','string','max:512','regex:/^\/[^\s]*$/'],
                'feed_type'   => 'nullable|in:random,all',
                'random_feed' => 'nullable|integer|min:1|max:50',
            ]);

            $payload = decryptUrl($request->eq);
            $domainName = $payload['domain'] ?? null;
            if (!$domainName) {
                return ['status' => false, 'message' => 'Invalid link.'];
            }

            // domain must be active
            $domain = DB::table('domains')->where('name', $domainName)->where('status', 1)->first();
            if (!$domain) {
                return ['status' => false, 'message' => 'Domain not found or inactive.'];
            }

            // Build full URL from decrypted domain + path (not a fixed base)
            $path = '/' . ltrim($request->input('feed_path'), '/');
            $urlHttps = 'https://' . rtrim($domainName, '/') . $path;

            // dd($urlHttps);

            // Use cURL to fetch the feed more efficiently
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlHttps);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            
            $body = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($status !== 200) {
                return [
                    'status'  => false,
                    'message' => "Could not fetch feed (HTTP {$status})",
                    'error' => $error
                ];
            }

            $body = preg_replace('/^\x{FEFF}/u', '', $body);
            $body = trim($body);

            // Try to detect encoding and convert to UTF-8 if needed
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
                return [
                    'status'  => false,
                    'message' => 'Invalid RSS Feed Format',
                    'error' => 'Invalid XML: ' . implode('; ', $errors)
                ];
            }

            // Check if it's actually an RSS feed
            if (!isset($xml->channel)) {
                return [
                    'status'  => false,
                    'message' => 'The URL does not appear to be a valid RSS feed',
                    'error' => 'Missing channel element in XML'
                ];
            }

            // Process the items from the XML
            $items = [];
            $itemCount = 0;
            $maxItems = 50; // Limit to prevent memory issues
            
            foreach ($xml->channel->item as $item) {
                if ($itemCount >= $maxItems) break;
                
                $title = trim((string) $item->title);
                $link = trim((string) $item->link);
                $description = trim((string) $item->description);
                
                // Clean description - remove HTML tags and trim
                $cleanDesc = strip_tags($description);
                $cleanDesc = preg_replace('/\s+/', ' ', $cleanDesc);
                $cleanDesc = trim($cleanDesc);
                
                // Extract image - try multiple methods
                $image = '';
                
                // Method 1: Check for media:content or media:thumbnail
                if (isset($item->children('media', true)->content)) {
                    $media = $item->children('media', true)->content;
                    $image = (string)$media->attributes()->url;
                } elseif (isset($item->children('media', true)->thumbnail)) {
                    $media = $item->children('media', true)->thumbnail;
                    $image = (string)$media->attributes()->url;
                }
                
                // Method 2: Check for enclosure
                if (empty($image) && isset($item->enclosure)) {
                    $enclosure = $item->enclosure;
                    if (strpos((string)$enclosure['type'], 'image/') === 0) {
                        $image = (string)$enclosure['url'];
                    }
                }
                
                // Method 3: Extract first image from description
                if (empty($image) && preg_match('/<img\s+[^>]*src="([^"]+)"/i', $description, $matches)) {
                    $image = $matches[1];
                }
                
                // Validate URL
                if (!empty($image) && !filter_var($image, FILTER_VALIDATE_URL)) {
                    $image = '';
                }
                
                // If we still don't have an image, try to get the site's favicon
                if (empty($image)) {
                    $domain = parse_url($link, PHP_URL_HOST);
                    if ($domain) {
                        $image = 'https://www.google.com/s2/favicons?domain=' . $domain;
                    }
                }
                
                $items[] = [
                    'title'       => $title,
                    'link'        => $link,
                    'description' => $cleanDesc,
                    'image'       => $image,
                ];
                
                $itemCount++;
            }

            // Handle random feed if requested
            if ($request->input('feed_type') == 'random') {
                $randomCount = min($request->input('random_feed', 2), count($items));
                shuffle($items); // Randomize the feed items
                $items = array_slice($items, 0, $randomCount); // Limit to the requested number
            }

            return [
                'status' => true,
                'items'  => $items,
                'count'  => count($items)
            ];

            return ['status' => true, 'items' => $items, 'count' => count($items)];

        } catch (\Throwable $e) {
            Log::error('RSS Fetch Error: '.$e->getMessage());
            return ['status' => false, 'message' => 'An error occurred: '.$e->getMessage()];
        }
    }

    private function curlGet(string $url, array $baseOpts): array
    {
        $ch = curl_init();
        $opts = $baseOpts + [CURLOPT_URL => $url];
        curl_setopt_array($ch, $opts);
        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno  = curl_errno($ch);
        $error  = curl_error($ch);
        curl_close($ch);
        return [$body, $status, $errno, $error];
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

            // Optimized LEFT JOIN; only needed columns
            $row = DB::table('domains as d')
                ->leftJoin('news_flasks as f', 'f.domain_id', '=', 'd.id')
                ->where('d.name', $domainName)
                ->where('d.status', 1)
                ->select(
                    'd.id as domain_id',
                    'd.name as domain_name',
                    'd.status as domain_status',
                    'f.feed_url','f.title','f.theme_color',
                    'f.exit_intent','f.after_seconds','f.scroll_down',
                    'f.show_again_after_minutes','f.enable_desktop','f.enable_mobile','f.status'
                )
                ->first();

            if (!$row) {
                return redirect()->route('domain.view')->with('error', 'Domain not found or inactive.');
            }

            $domain = (object)[
                'id' => $row->domain_id,
                'name' => $row->domain_name,
                'status' => $row->domain_status,
            ];

            $flask = $row->feed_url ? (object)[
                'feed_url' => $row->feed_url,
                'title' => $row->title,
                'theme_color' => $row->theme_color,
                'exit_intent' => (bool)$row->exit_intent,
                'after_seconds' => $row->after_seconds,
                'scroll_down' => (bool)$row->scroll_down,
                'show_again_after_minutes' => (int)($row->show_again_after_minutes ?? 5),
                'enable_desktop' => (bool)($row->enable_desktop ?? 1),
                'enable_mobile' => (bool)($row->enable_mobile ?? 1),
                'status' => (bool)($row->status ?? 1),
            ] : null;

            return view('newshub::flask', [
                'domain' => $domain,
                'flask'  => $flask,
                'eq'     => $request->eq, // pass eq for save/fetch
            ]);

        } catch (\Throwable $th) {
            return redirect()->route('domain.view')->with('error', 'Invalid or expired link.');
        }
    }

    public function flaskSave(Request $request)
    {
        try {
            
            $validated = $request->validate([
                'eq'                        => ['required','string'],
                'feed_path'                 => ['required','string','max:512','regex:/^\/[^\s]*$/'],
                'title'                     => ['required','string','max:255'],
                'theme_color'               => ['required','regex:/^#(?:[0-9A-Fa-f]{3}){1,2}$/'],
                'after_seconds_toggle'      => ['nullable','in:on,1'],
                'after_seconds'             => ['nullable','integer','min:1','max:86400','required_with:after_seconds_toggle'],
                'show_again_after_minutes'  => ['required','integer','min:0','max:10080'],
            ], [
                'theme_color.regex'         => 'Theme color must be a valid hex like #fd683e',
                'after_seconds.required_with' => 'Please select the number of seconds.',
            ]);

            // Resolve domain from eq
            $payload = decryptUrl($validated['eq']);
            $domainName = $payload['domain'] ?? null;
            if (!$domainName) {
                return back()->with('error','Invalid link.')->withInput();
            }
            $domain = DB::table('domains')->select('id','status')->where('name',$domainName)->first();
            if (!$domain || (int)$domain->status !== 1) {
                return back()->with('error','Domain not found or inactive.')->withInput();
            }

            // Build full URL from domain + path
            $path        = '/' . ltrim($validated['feed_path'], '/');
            $fullFeedUrl = 'https://' . rtrim($domainName, '/') . $path;

            // Checkbox triggers
            $exitIntent   = $request->has('exit_intent') ? 1 : 0;
            $hasSeconds   = $request->has('after_seconds_toggle');
            $afterSeconds = $hasSeconds ? (int)($validated['after_seconds'] ?? 0) : null;
            $scrollDown   = $request->has('scroll_down') ? 1 : 0;

            // Require at least one trigger (server-side)
            if (!$exitIntent && !$hasSeconds && !$scrollDown) {
                return back()->withErrors(['trigger' => 'Select at least one trigger option.'])->withInput();
            }

            // Switches
            $enableDesktop = $request->has('enable_desktop') ? 1 : 0;
            $enableMobile  = $request->has('enable_mobile')  ? 1 : 0;

            // Upsert (no status field here, as requested)
            DB::table('news_flasks')->updateOrInsert(
                ['domain_id' => (int)$domain->id],
                [
                    'feed_url'                  => $fullFeedUrl,
                    'title'                     => $validated['title'],
                    'theme_color'               => $validated['theme_color'],
                    'exit_intent'               => $exitIntent,
                    'after_seconds'             => $afterSeconds,
                    'scroll_down'               => $scrollDown,
                    'show_again_after_minutes'  => (int)$validated['show_again_after_minutes'],
                    'enable_desktop'            => $enableDesktop,
                    'enable_mobile'             => $enableMobile,
                    'updated_at'                => now(),
                    'created_at'                => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
            
            // clear cache 
            if($domainName) {
                Cache::forget("apsh_cfg_{$domainName}");
            }

            return redirect()->route('news-hub.index')->with('success','News Flask saved.');
        } catch (\Throwable $th) {
           return redirect()->back()->with('error','Error : '.$th->getMessage());
        }
    }

    // NEW BOTTOM SLIDER
    public function bottomSlider(Request $request)
    {
        try {
            $request->validate(['eq' => 'required|string']);
            $payload = decryptUrl($request->eq);
            $domainName = $payload['domain'] ?? null;
            if (!$domainName) return redirect()->route('domain.view')->with('error','Invalid link.');

            // Join only what we need
            $row = DB::table('domains as d')
                ->leftJoin('news_bottom_sliders as s', 's.domain_id', '=', 'd.id')
                ->where('d.name', $domainName)->where('d.status', 1)
                ->select(
                    'd.id as domain_id', 'd.name as domain_name', 'd.status as domain_status',
                    's.feed_url', 's.theme_color','s.mode','s.posts_count',
                    's.enable_desktop','s.enable_mobile'
                )->first();

            if (!$row) return redirect()->route('domain.view')->with('error','Domain not found or inactive.');

            $domain = (object)['id'=>$row->domain_id,'name'=>$row->domain_name,'status'=>$row->domain_status];

            $slider = $row->feed_url ? (object)[
                'feed_url'       => $row->feed_url,
                'theme_color'    => $row->theme_color,
                'mode'           => $row->mode,
                'posts_count'    => (int)($row->posts_count ?? 8),
                'enable_desktop' => (bool)($row->enable_desktop ?? 1),
                'enable_mobile'  => (bool)($row->enable_mobile ?? 1),
            ] : null;

            return view('newshub::bottom-slider', [
                'domain' => $domain,
                'slider' => $slider,
                'eq'     => $request->eq,
            ]);
        } catch (\Throwable $th) {
            return redirect()->route('domain.view')->with('error','Invalid or expired link.');
        }
    }

    public function bottomSliderSave(Request $request)
    {
        try {
            $validated = $request->validate([
                'eq'             => ['required','string'],
                'feed_path'      => ['required','string','max:512','regex:/^\/[^\s]*$/'],
                'theme_color'    => ['required','regex:/^#(?:[0-9A-Fa-f]{3}){1,2}$/'],
                'mode'           => ['required', Rule::in(['latest','random'])],
                'posts_count'    => ['required','integer','min:1','max:50'],
                // toggles handled via ->has()
            ], [
                'theme_color.regex' => 'Theme color must be a valid hex like #000000 or #fd683e',
            ]);

            $payload = decryptUrl($validated['eq']);
            $domainName = $payload['domain'] ?? null;
            if (!$domainName) return back()->with('error','Invalid link.')->withInput();

            $domain = DB::table('domains')->select('id','status')->where('name',$domainName)->first();
            if (!$domain || (int)$domain->status !== 1) return back()->with('error','Domain not found or inactive.')->withInput();

            $path        = '/' . ltrim($validated['feed_path'], '/');
            $fullFeedUrl = 'https://' . rtrim($domainName, '/') . $path;

            DB::table('news_bottom_sliders')->updateOrInsert(
                ['domain_id' => (int)$domain->id],
                [
                    'feed_url'       => $fullFeedUrl,
                    'theme_color'    => $validated['theme_color'],
                    'mode'           => $validated['mode'],
                    'posts_count'    => (int)$validated['posts_count'],
                    'enable_desktop' => $request->has('enable_desktop') ? 1 : 0,
                    'enable_mobile'  => $request->has('enable_mobile')  ? 1 : 0,
                    'updated_at'     => now(),
                    'created_at'     => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
            
            // clear cache 
            if($domainName) {
                Cache::forget("apsh_cfg_{$domainName}");
            }

            return redirect()->route('news-hub.index')->with('success','Bottom Slider saved.');
        } catch (\Throwable $th) {
           return redirect()->back()->with('error','Error : '.$th->getMessage());
        }
    }

    public function newsHub(Request $request)
    {
        $site = trim(strtolower($request->query('site', '')));
        if ($site === '') {
            return response('// apsh: missing ?site', 200)
                ->header('Content-Type','application/javascript')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        }

        // cache config for 4 hours
        $cfg = Cache::remember("apsh_cfg_{$site}", now()->addHours(4), function() use ($site) {
            // 1) domain (raw, tiny payload)
            $domain = DB::table('domains')
                ->select('id','name','status')
                ->where('name', $site)
                ->where('status', 1)
                ->first();

            if (!$domain) return ['ok' => false];

            // 2) rolls (active only)
            $rolls = DB::table('news_rolls')
                ->select([
                    'id','feed_url','title','icon','theme_color',
                    'widget_placement','show_on_desktop','show_on_mobile'
                ])
                ->where('domain_id', $domain->id)
                ->where('status', 1)
                ->orderByDesc('id')
                ->get()
                ->map(function($r){
                    // cast to expected types
                    $r->show_on_desktop = (bool)$r->show_on_desktop;
                    $r->show_on_mobile  = (bool)$r->show_on_mobile;
                    return $r;
                })
                ->values();

            // 3) flasks (active only)
            $flasks = DB::table('news_flasks')
                ->select([
                    'id','feed_url','title','theme_color','exit_intent','after_seconds','scroll_down',
                    'show_again_after_minutes','enable_desktop','enable_mobile'
                ])
                ->where('domain_id', $domain->id)
                ->where('status', 1)
                ->orderByDesc('id')
                ->get()
                ->map(function($f){
                    $f->exit_intent   = (bool)$f->exit_intent;
                    $f->scroll_down   = (bool)$f->scroll_down;
                    $f->enable_desktop= (bool)$f->enable_desktop;
                    $f->enable_mobile = (bool)$f->enable_mobile;
                    // ints
                    $f->after_seconds = $f->after_seconds !== null ? (int)$f->after_seconds : null;
                    $f->show_again_after_minutes = (int)$f->show_again_after_minutes;
                    return $f;
                })
                ->values();

            // 4) bottom sliders (active only)
            $sliders = DB::table('news_bottom_sliders')
                ->select([
                    'id','feed_url','theme_color','mode','posts_count','enable_desktop','enable_mobile'
                ])
                ->where('domain_id', $domain->id)
                ->where('status', 1)
                ->orderByDesc('id')
                ->get()
                ->map(function($s){
                    $s->enable_desktop = (bool)$s->enable_desktop;
                    $s->enable_mobile  = (bool)$s->enable_mobile;
                    $s->posts_count    = (int)$s->posts_count;
                    return $s;
                })
                ->values();

            return [
                'ok'      => true,
                'site'    => $domain->name,
                'rolls'   => $rolls,
                'flasks'  => $flasks,
                'sliders' => $sliders,
                'v'       => 1,
            ];
        });

        return response()
            ->view('newshub::script', ['cfg' => $cfg])
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

}
