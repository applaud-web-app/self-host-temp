<?php

namespace Modules\URLShortener\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\URLShortener\Models\UrlShorter;
use Illuminate\Support\Str; 
use Yajra\DataTables\DataTables;

class URLShortenerController extends Controller
{
    public function index()
    {
        return view('urlshortener::index');
    }

    public function youtube(Request $request)
    {
        if ($request->ajax()) {
            $query = UrlShorter::where('type', 'yt')
                ->select(['id', 'target_url', 'short_url', 'created_at', 'status']);

            // Unified text search across channel handle (@handle), full target_url, and short_url
            if ($request->filled('search_term')) {
                $term = trim($request->search_term);

                // If the user types with @, normalize both cases
                $cleanHandle = ltrim($term, '@');

                $query->where(function ($q) use ($term, $cleanHandle) {
                    // Match full target URL
                    $q->where('target_url', 'like', '%' . $term . '%')
                      // Match @handle inside the URL
                      ->orWhere('target_url', 'like', '%@' . $cleanHandle . '%')
                      // Match short URL
                      ->orWhere('short_url', 'like', '%' . $term . '%');
                });
            }

            // Filter by exact channel from dropdown (Select2)
            if ($request->filled('channel_list')) {
                $selected = ltrim($request->channel_list, '@'); // accept with/without '@'
                $query->where('target_url', 'like', '%@' . $selected . '%');
            }

            // Filter by status
            if ($request->filled('filter_status')) {
                $status = (int) $request->filter_status;
                $query->where('status', $status);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('target_url', function ($row) {
                    // Extract @channelname and render a link + copy icon
                    $channelName = preg_replace('/^https?:\/\/(www\.)?youtube\.com\/@/i', '', $row->target_url);
                    $channelAnchor = '<a href="' . e($row->target_url) . '" target="_blank">@' . e($channelName) . '</a>';
                    $copyBtn = '<button type="button" class="btn btn-link p-0 ms-2 copy-url" data-url="' . e($row->target_url) . '" title="Copy channel URL"><i class="far fa-copy"></i></button>';
                    return $channelAnchor . $copyBtn;
                })
                ->editColumn('short_url', function ($row) {
                    $shortAnchor = '<a href="' . e($row->short_url) . '" target="_blank">' . e($row->short_url) . '</a>';
                    $copyBtn = '<button type="button" class="btn btn-link p-0 ms-2 copy-url" data-url="' . e($row->short_url) . '" title="Copy short URL"><i class="far fa-copy"></i></button>';
                    return $shortAnchor . $copyBtn;
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('d-M, Y');
                })
                ->addColumn('status', function ($row) {
                    $checked = $row->status == 1 ? 'checked' : '';
                    return '<div class="form-check form-switch">
                                <input class="form-check-input status_input" type="checkbox" role="switch" ' . $checked . ' data-id="' . e($row->id) . '">
                            </div>';
                })
                ->addColumn('actions', function ($row) {
                    return '<button type="button" class="btn btn-sm btn-danger" onclick="deleteUrl(' . e($row->id) . ')" title="Delete"> <i class="fas fa-trash"></i></button>';
                })
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

            $row = UrlShorter::where('type', 'yt')->findOrFail($request->id);
            $row->status = (int) $request->status;
            $row->save();

            return response()->json([
                'success' => true,
                'message' => $row->status ? 'Channel link activated.' : 'Channel link deactivated.',
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
            $row = UrlShorter::where('type', 'yt')->findOrFail($id);
            $row->delete();

            return response()->json([
                'success' => true,
                'message' => 'Entry deleted successfully.',
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
            $shortUrl = route('api.shorturl.subs', ['type' => 'yt', 'code' => $uniqueCode]);

            // Ensure target_url is unique
            $urlExists = UrlShorter::where('target_url', $validated['channel_url'])->exists();
            if ($urlExists) {
                return back()->with('error', 'This YouTube URL is already shortened.');
            }

            // Save the data to the database
            $urlShorter = UrlShorter::create([
                'target_url' => $validated['channel_url'],
                'short_url' => $shortUrl,
                'prompt' => $validated['prompt'],
                'type' => 'yt',  // type is 'yt' for YouTube
                'forced_subscribe' => $forcedSubscribe, // Ensure 'forced_subscribe' is handled
                'status' => 1,  // Set status to active or as required
            ]);

            return redirect()->route('url_shortener.youtube')->with('success', 'YouTube URL shortened successfully!');
        } catch (\Throwable $th) {
            dd($th->getMessage()); // You can remove dd() in production
            return back()->with('error', 'An error occurred. Please try again later.');
        }
    }
}
