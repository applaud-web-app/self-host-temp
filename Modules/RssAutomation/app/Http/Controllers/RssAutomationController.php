<?php

namespace Modules\RssAutomation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RssAutomation\Models\RssFeed;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;
use Exception;
use DateTime;
use App\Models\Domain;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RssAutomationController extends Controller
{
    public function create()
    {
        return view('rssautomation::create');  // Points to the view that we will create
    }

    public function view(Request $request)
    {
        if ($request->ajax()) {
            $query = RssFeed::query();

            // Filter by Feed Name or Feed URL (search by name or URL)
            if ($request->filled('search_name')) {
                $searchTerm = $request->search_name;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('url', 'like', '%'.$searchTerm.'%');
                });
            }

            // Filter by Feed Type (latest or random)
            if ($request->filled('status') && in_array($request->status, ['latest', 'random'])) {
                $query->where('type', $request->status);
            }

            // Sorting based on "Old to New" or "New to Old"
            if ($request->filled('order_by')) {
                $order = $request->order_by == 'old_to_new' ? 'asc' : 'desc';
                $query->orderBy('created_at', $order);
            }

            $query->where('is_active', '!=', 2);  // Exclude inactive feeds

            // Server-side processing with DataTables
            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('feed_info', function($row) {
                    $url = strlen($row->url) > 80 ? substr($row->url, 0, 80) . '...' : $row->url;
                    return $row->name . ' - <a href="' . $row->url . '" class="text-primary" target="_blank">' . $url . '</a>';
                })
                ->editColumn('created_at', fn($row) => $row->created_at->format('d-M, Y h:i A'))
                ->addColumn('sent_time', function($row) {
                    $startTime = DateTime::createFromFormat('H:i:s', $row->start_time)->format('h:i A');
                    $endTime = DateTime::createFromFormat('H:i:s', $row->end_time)->format('h:i A');
                    return $startTime . ' - ' . $endTime;
                })
                ->addColumn('time_diff', function($row) {
                    $minutes = $row->interval_min;
                    if ($minutes >= 60) {
                        $hours = floor($minutes / 60);
                        $remainingMinutes = $minutes % 60;
                        return "{$hours} hr" . ($hours > 1 ? '\'s' : '') . " {$remainingMinutes} min" . ($remainingMinutes > 1 ? '\'s' : '');
                    } else {
                        return "{$minutes} min" . ($minutes > 1 ? '\'s' : '');
                    }
                })
                // ->addColumn('last_send', fn() => '---')
                ->addColumn('last_send', function($row) {
                    $now = Carbon::now();
                    $lastSentAt = $row->notifications()->latest('last_sent_at')->first();

                    if (!$lastSentAt) {
                        return '---';
                    }

                    // Format the main date
                    $formattedDate = Carbon::parse($lastSentAt->last_sent_at)->format('j F, Y');
                    // Format the time
                    $formattedTime = Carbon::parse($lastSentAt->last_sent_at)->format('H:i A');

                    return $formattedDate . '<br><small>' . $formattedTime . '</small>';
                })
                ->addColumn('type', function($row) {
                    return $row->type == 'latest' ? 
                        '<span class="badge bg-danger">Latest</span>' :
                        '<span class="badge bg-secondary">Random: ' . $row->random_count . '</span>';
                })
                ->addColumn('status', function ($row) {
                    $param = ['id' => $row->id];
                    $statusUrl = encryptUrl(route('rss.status'), $param);
                    $checked = $row->is_active == 1 ? "checked" : "";
                    return  '<div class="form-check form-switch">
                                <input class="form-check-input status_input" data-url="' . $statusUrl . '" type="checkbox" role="switch" ' . $checked . '>
                            </div>';
                })
                ->addColumn('actions', function ($row) {
                    $param = ['id' => $row->id];
                    $editUrl = encryptUrl(route('rss.edit'), $param);
                    $deleteUrl = encryptUrl(route('rss.delete'), $param);
                    $reportUrl = encryptUrl(route('rss.report'), $param);

                    return '<div class="d-flex align-items-center gap-2">
                        <a href=" ' .$reportUrl.' " class="icon-rss light btn btn-sm btn-secondary"><i class="fas fa-eye"></i></a>
                        <a href="' . $editUrl . '" class="icon-rss light btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                        <button class="icon-rss light btn btn-sm btn-danger delete-btn" data-url="' . $deleteUrl . '"><i class="fas fa-trash"></i></button>
                    </div>';
                })
                ->rawColumns(['feed_info','last_send','actions', 'status', 'type'])
                ->make(true);
        }

        return view('rssautomation::view');
    }

    public function store(Request $request)
    {
        // Validate the input
        $validator = validator::make($request->all(), [
            'rssfeedname' => 'required|max:100',
            'rssFeedUrl' => 'required|url',
            'daily_start_time' => 'required|date_format:H:i',
            'daily_end_time' => 'required|date_format:H:i|after:daily_start_time',
            'interval_minutes' => 'required|integer|min:5',
            'feed_type' => 'required|in:latest,random',
            'random_feed' => 'required_if:feed_type,random|integer|min:1',
            'cta_enabled' => 'nullable|accepted', 
            'button_1_title' => 'nullable|string',
            'button_1_url' => 'nullable|url',
            'button_2_title' => 'nullable|string',
            'button_2_url' => 'nullable|url',
            'banner_image' => 'nullable|url',
            'banner_icon' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $dataToStore = [
            'name' => $request->rssfeedname,
            'url' => $request->rssFeedUrl,
            'type' => $request->feed_type,
            'random_count' => $request->random_feed,
            'start_time' => $request->daily_start_time,
            'end_time' => $request->daily_end_time,
            'interval_min' => $request->interval_minutes,
            'icon' => $request->banner_icon,
            'cta_enabled' => $request->cta_enabled == "on" ? true : false,
            'is_active' => true,
        ];

        if ($request->cta_enabled == "on") {
            $dataToStore['button1_title'] = $request->button_1_title;
            $dataToStore['button1_url'] = null; // $request->button_1_url
            $dataToStore['button2_title'] = $request->button_2_title;
            $dataToStore['button2_url'] = null; // $request->button_2_url
        } else {
            $dataToStore['button1_title'] = null;
            $dataToStore['button1_url'] = null;
            $dataToStore['button2_title'] = null;
            $dataToStore['button2_url'] = null;
        }

        try {
            RssFeed::create($dataToStore);
            return redirect()->route('rss.view')->with('success', 'RSS Feed submitted successfully!');
        } catch (Exception $e) {
            \Log::error('Error storing RSS Feed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'There was an error while saving the RSS Feed. Please try again.');
        }
    }

    public function fetch(Request $request): array
    {
        try {
            $request->validate([
                'feed' => 'required|url'
            ]);
            
            $url = $request->input('feed');
            $dname = parse_url($url, PHP_URL_HOST);
            if (!Domain::where('name', $dname)->where('status', 1)->exists()) {
                return [
                    'status'  => false,
                    'message' => 'The domain is not active or not part of the system.',
                ];
            }

            // Use cURL to fetch the feed more efficiently
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
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

        } catch (\Exception $e) {
            Log::error('RSS Fetch Error: ' . $e->getMessage());
            return [
                'status'  => false,
                'message' => 'An error occurred while fetching the feed: ' . $e->getMessage(),
                'error' => $e->getTraceAsString()
            ];
        }
    }

    public function edit(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'eq' => 'required|string',
            ]);

            // Decrypt the URL to get the RSS Feed ID
            $response = decryptUrl($request->eq);
            $rssId   = $response['id'];  // Assuming the 'id' is in the decrypted data

            // Fetch the RSS feed from the database
            $feed = RssFeed::findOrFail($rssId);
            $param = ['id' => $feed->id];
            $action = encryptUrl(route('rss.update'), $param);

            // Return the view with the feed data
            return view('rssautomation::edit', compact('feed', 'action'));

        } catch (\Throwable $th) {
            // Redirect to the RSS view page with an error message if something goes wrong
            return redirect()->route('rss.view')->with('error', 'Failed to load edit view: ' . $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        // Validate the input
        $validator = Validator::make($request->all(), [
            'rssfeedname' => 'required|max:100',
            'rssFeedUrl' => 'required|url',
            'daily_start_time' => 'required|date_format:H:i',
            'daily_end_time' => 'required|date_format:H:i|after:daily_start_time',
            'interval_minutes' => 'required|integer|min:5',
            'feed_type' => 'required|in:latest,random',
            'random_feed' => 'required_if:feed_type,random|integer|min:1',
            'cta_enabled' => 'nullable|accepted',
            'button_1_title' => 'nullable|string',
            'button_1_url' => 'nullable|url',
            'button_2_title' => 'nullable|string',
            'button_2_url' => 'nullable|url',
            'banner_image' => 'nullable|url',
            'banner_icon' => 'nullable',
            'eq' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $response = decryptUrl($request->eq);
        $rssId = $response['id'];

        try {
            $dataToUpdate = [
                'name' => $request->rssfeedname,
                'type' => $request->feed_type,
                'random_count' => $request->random_feed,
                'start_time' => $request->daily_start_time,
                'end_time' => $request->daily_end_time,
                'interval_min' => $request->interval_minutes,
                'icon' => $request->banner_icon,
                'cta_enabled' => $request->cta_enabled == "on" ? true : false, 
            ];

            if ($request->cta_enabled == "on") {
                $dataToUpdate['button1_title'] = $request->button_1_title;
                $dataToUpdate['button1_url'] = null; // $request->button_1_url
                $dataToUpdate['button2_title'] = $request->button_2_title;
                $dataToUpdate['button2_url'] = null; // $request->button_2_url
            } else {
                $dataToUpdate['button1_title'] = null;
                $dataToUpdate['button1_url'] = null;
                $dataToUpdate['button2_title'] = null;
                $dataToUpdate['button2_url'] = null;
            }

            RssFeed::where('id', $rssId)->update($dataToUpdate);
            return redirect()->route('rss.view')->with('success', 'RSS Feed updated successfully!');
        } catch (Exception $e) {
            \Log::error('Error storing RSS Feed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'There was an error while updating the RSS Feed. Please try again.');
        }
    }

    public function delete(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'eq' => 'required|string',
            ]);

            // Decrypt the URL and get the ID
            $response = decryptUrl($request->eq);
            $rssId = $response['id'];

            // Find the RSS feed by ID and delete it
            $feed = RssFeed::findOrFail($rssId);
            $feed->is_active = 2;  // Mark as deleted (or inactive)
            $feed->save();

            return response()->json(['status' => true, 'message' => 'RSS Feed deleted successfully!']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => 'Failed to delete RSS Feed: ' . $th->getMessage()]);
        }
    }

    public function status(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'eq' => 'required|string',
            ]);

            // Decrypt the URL and get the ID
            $response = decryptUrl($request->eq);
            $rssId = $response['id'];

            // Find the RSS feed by ID and toggle its status
            $feed = RssFeed::findOrFail($rssId);
            $feed->is_active = $feed->is_active == 1 ? 0 : 1;  // Toggle status
            $feed->save();

            return response()->json(['status' => true, 'message' => 'RSS Feed status updated successfully!']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => 'Failed to update RSS Feed status: ' . $th->getMessage()]);
        }
    }

    public function report(Request $request)
    {
        try {
            // Validate the request - make 'eq' optional
            $request->validate([
                'eq' => 'nullable|string',
            ]);

            // Check if 'eq' exists in the request, otherwise set $rssId to null (for fetching all data)
            if ($request->filled('eq')) {
                // Decrypt URL and get RSS Feed ID if 'eq' is provided
                $response = decryptUrl($request->eq);
                $rssId = $response['id'];
                // Find The RSS Feed
                $feed = RssFeed::findOrFail($rssId);
            } else {
                // If 'eq' is not provided, set $rssId to null and show all data
                $rssId = null;
                $feed = null; // No specific feed to display
            }

            // Fetch notifications - use the condition for filtering by $rssId
            $query = DB::table('notifications as n')
                ->leftJoin('rss_feed_notifications as rfn', 'n.id', '=', 'rfn.notification_id')
                ->leftJoin('domain_notification as dn', 'n.id', '=', 'dn.notification_id')
                ->leftJoin('domains as d', 'd.id', '=', 'dn.domain_id')
                ->leftJoin('push_event_counts as pec', function ($join) {
                    $join->on('pec.message_id', '=', 'n.message_id')
                        ->on('pec.domain', '=', 'd.name')
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
                    DB::raw('COALESCE(SUM(pec.count), 0) as clicks'),
                ]);

            // If a specific RSS Feed is provided, filter by it, otherwise show all data
            if ($rssId) {
                $query->where('rfn.rss_feed_id', $rssId);
            }

            $query->where('n.segment_type', 'rss') // Filter by 'rss' segment type
                ->groupBy(
                    'n.id',
                    'n.campaign_name',
                    'n.schedule_type',
                    'n.segment_type',
                    'n.title',
                    'd.name',
                    'dn.sent_at',
                    'dn.status'
                );

            // Apply dynamic filters
            $query->when($request->filled('status'), fn ($q) => $q->where('dn.status', $request->status))
                ->when($request->filled('search_term'), function ($q) use ($request) {
                    $term = "%{$request->search_term}%";
                    $q->where(function ($sub) use ($term) {
                        $sub->where('n.campaign_name', 'like', $term)
                            ->orWhere('n.title', 'like', $term);
                    });
                })
                ->when($request->filled('site_web'), fn ($q) => $q->where('d.name', $request->site_web))
                ->when($request->filled('last_send'), function ($q) use ($request) {
                    [$start, $end] = explode(' - ', $request->last_send);
                    $q->whereBetween('n.one_time_datetime', [
                        Carbon::createFromFormat('m/d/Y', $start)->startOfDay(),
                        Carbon::createFromFormat('m/d/Y', $end)->endOfDay(),
                    ]);
                });

            // Sorting by the notification ID
            $query->orderBy('n.id', 'desc');

            // If the request is ajax, return the data for the DataTable
            if ($request->ajax()) {
                // Return DataTables response
                return datatables()->of($query)
                    ->addIndexColumn()
                    ->addColumn('campaign_name', function ($row) {
                        $truncated = Str::limit($row->title, 50, '…');
                        return '<div>' . e($row->campaign_name) . '<small class="ms-1 text-primary text-capitalize">[' . e($row->schedule_type) . ']</small><br><small> ' . e($truncated) . '</small></div>';
                    })
                    ->addColumn('status', function ($row) {
                        $map = [
                            'pending'   => ['badge-warning', 'Pending'],
                            'queued'    => ['badge-info', 'Processing'],
                            'sent'      => ['badge-success', 'Sent'],
                            'failed'    => ['badge-danger', 'Failed'],
                            'cancelled' => ['badge-secondary', 'Cancelled'],
                        ];
                        [$class, $label] = $map[$row->status] ?? ['badge-secondary', ucfirst($row->status)];
                        return "<span class=\"badge {$class}\">{$label}</span>";
                    })
                    ->addColumn('sent_time', function ($row) {
                        if ($row->sent_time) {
                            $dt = Carbon::parse($row->sent_time);
                            $date = $dt->format('d M, Y');
                            $time = $dt->format('H:i A');
                            return "{$date}<br><small>{$time}</small>";
                        }
                        return '—';
                    })
                    ->addColumn('clicks', fn ($row) => $row->clicks)
                    ->addColumn('action', function ($row) {
                        $param = ['notification' => $row->id, 'domain' => $row->domain];
                        $detailsUrl = encryptUrl(route('notification.details'), $param);
                        $html = '<button type="button" class="btn btn-primary light btn-sm report-btn rounded-pill"
                                    data-bs-toggle="modal" data-bs-target="#reportModal" data-url="' . $detailsUrl . '">
                                    <i class="fas fa-analytics"></i>
                                </button>';
                        return $html;
                    })
                    ->rawColumns(['campaign_name', 'status', 'sent_time', 'action'])
                    ->make(true);
            }

            // Return the view if it's not an AJAX request
            return view('rssautomation::report', compact('feed'));

        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'There was an error while fetching the RSS Feed report. Please try again.');
        }
    }


}
