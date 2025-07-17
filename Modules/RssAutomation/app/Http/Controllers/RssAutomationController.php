<?php

namespace Modules\RssAutomation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RssAutomation\Models\RssFeed;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class RssAutomationController extends Controller
{
    public function create()
    {
        return view('rssautomation::create');  // Points to the view that we will create
    }

    public function report()
    {
        return view('rssautomation::report');
    }

    public function store(Request $request)
    {
        // You can save to DB later
        return redirect()->route('rss.report')->with('success', 'RSS Feed submitted!');
    }

    public function fetch(Request $request): array
    {
        try {
            $request->validate([
                'feed' => 'required|url'
            ]);
            
            $url = $request->input('feed');
            
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

}
