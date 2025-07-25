<?php

namespace Modules\RssAutomation\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\RssAutomation\Models\RssFeed;
use Modules\RssAutomation\Models\RssFeedNotification;
use App\Models\Notification;
use App\Jobs\SendNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Domain;
use Illuminate\Support\Str;

class SendRssNotifications extends Command
{
    protected $signature = 'rss:send-notifications';
    protected $description = 'Send RSS feed notifications';

    protected const CHUNK_SIZE = 10;
    protected int $totalDispatched = 0;

    public function handle()
    {
        $now = Carbon::now();
        Log::info('Starting RSS dispatch', ['time' => $now->toDateTimeString()]);

        try {
            RssFeed::query()
                ->where('is_active', 1)
                ->whereColumn('start_time', '<', 'end_time')
                ->whereTime('start_time', '<=', $now->toTimeString())
                ->whereTime('end_time', '>', $now->toTimeString())
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($feeds) use ($now) {
                    try {
                        foreach ($feeds as $feed) {
                            $notificationRecord = RssFeedNotification::where('rss_feed_id', $feed->id)
                                ->whereDate('last_sent_at', $now->toDateString())
                                ->orderBy('last_sent_at', 'desc')
                                ->first();

                            // If no record found or if interval has passed
                            if (!$notificationRecord || $this->shouldSendNotification($notificationRecord->last_sent_at, $feed->interval_min)) {

                                $randomCount = ($feed->type === 'random' && $feed->random_count)
                                ? (int) $feed->random_count
                                : 1;

                                $rssData = $this->fetchRssFeed($feed->url, $feed->type, $randomCount);
                                $itemToSend = $this->selectItemToSend($rssData, $feed->type);

                                if ($itemToSend) {
                                    $data = $this->prepareNotificationData($feed, $itemToSend);
                                    $notification = $this->sendNotification($feed->id, $data);
                                    if ($notification) {
                                        $this->saveNotificationRecord($feed->id, $notification->id, $now);
                                        $this->totalDispatched++;
                                    } else {
                                        Log::warning("Skipping notification for RSS Feed ID {$feed->id}, domain not found.");
                                    }
                                } else {
                                    Log::warning("No valid item to send for RSS Feed ID {$feed->id}");
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::error('Error processing RSS chunk', [
                            'batch_ids' => $feeds->pluck('id')->toArray(),
                            'error'     => $e->getMessage(),
                        ]);
                    }
                });

        } catch (\Throwable $e) {
            Log::error('Error dispatching RSS notifications', ['error' => $e->getMessage()]);
        }

        Log::info('Finished RSS dispatch', ['total_dispatched' => $this->totalDispatched]);
    }

    private function fetchRssFeed(string $url, string $feedType, int $randomCount = 1)
    {
        // Retry logic for fetching RSS feed with timeouts and error handling
        return retry(3, function () use ($url, $feedType, $randomCount) {
            try {
                // Use Laravel's HTTP client to fetch the RSS feed data
                $response = Http::timeout(30)  // Set timeout for the request
                    ->withHeaders([
                        'Accept' => 'application/xml'  // Set Accept header for XML response
                    ])
                    ->get($url);  // Make the GET request to fetch the RSS feed

                // Check if the request was successful (HTTP 200)
                if ($response->failed()) {
                    throw new \Exception("Could not fetch feed (HTTP {$response->status()})");
                }

                $body = $response->body();
                $body = preg_replace('/^\x{FEFF}/u', '', $body); // Remove BOM if present
                $body = trim($body);

                // Try to detect encoding and convert to UTF-8 if needed
                $encoding = mb_detect_encoding($body, ['UTF-8', 'ISO-8859-1', 'WINDOWS-1252'], true);
                if ($encoding && $encoding !== 'UTF-8') {
                    $body = mb_convert_encoding($body, 'UTF-8', $encoding);
                }

                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);

                if ($xml === false) {
                    $errors = array_map(function ($e) {
                        return trim($e->message) . " (Line {$e->line})";
                    }, libxml_get_errors());
                    libxml_clear_errors();
                    throw new \Exception('Invalid RSS Feed Format: ' . implode('; ', $errors));
                }

                if (!isset($xml->channel)) {
                    throw new \Exception('The URL does not appear to be a valid RSS feed');
                }

                // Process the items from the XML
                $items = [];
                $itemCount = 0;
                $maxItems = 50;

                foreach ($xml->channel->item as $item) {
                    if ($itemCount >= $maxItems) break;

                    $title = trim((string) $item->title);
                    $link = trim((string) $item->link);
                    $description = trim((string) $item->description);

                    $cleanDesc = strip_tags($description);
                    $cleanDesc = preg_replace('/\s+/', ' ', $cleanDesc);
                    $cleanDesc = trim($cleanDesc);

                    // Extract image
                    $image = '';
                    // Logic to extract image...

                    $items[] = [
                        'title'       => $title,
                        'link'        => $link,
                        'description' => $cleanDesc,
                        'image'       => $image,
                    ];

                    $itemCount++;
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
                    'error'   => $e->getTraceAsString()
                ];
            }
        }, 3);  // Retry up to 3 times
    }


    private function selectItemToSend(array $rssData, string $feedType)
    {
        if (!$rssData['status']) {
            Log::error("RSS Fetch Error: " . $rssData['message']);
            return null;
        }

        $items = $rssData['items'];
        if (empty($items)) {
            return null;
        }

        if ($feedType === 'random' && count($items) > 1) {
            return $items[array_rand($items)];
        }

        return $items[0];
    }

    private function prepareNotificationData($feed, $item)
    {
        $title = Str::limit(trim($item['title']), 255);
        $description = Str::limit(trim($item['description']), 255);
        $link = parse_url(trim($item['link']), PHP_URL_HOST);
        $link = "https://" . Str::limit($link, 255);


        $image = trim($item['image']);
        // if (strlen($image) > 225) {
        //     $image = null;
        // } else {
        //     $image = Str::limit($image, 225);
        // }

        $targetUrl = strlen($item['link']) > 225 ? $link : $item['link'];
        
        return [
            'target_url'        => $targetUrl,
            'campaign_name'     => 'CAMP#' . random_int(1000, 9999),
            'title'             => $title,
            'description'       => $description,
            'banner_image'      => $image,
            'banner_icon'       => $feed->icon,
            'schedule_type'     => 'instant',
            'segment_type'      => 'rss',
            'segment_id'        => null,
            'btn_1_title'       => $feed->button1_title,
            'btn_1_url'         => $targetUrl, // $feed->button1_url
            'btn_title_2'       => $feed->button2_title,
            'btn_url_2'         => $targetUrl, // $feed->button2_url
        ];
    }

    private function sendNotification($feedId, $data)
    {

        $dname = parse_url($data['target_url'], PHP_URL_HOST);
        $domain = Domain::where('name', $dname)->where('status', 1)->first();
        if (!$domain) {
            // Mark the feed as inactive
            $feed = RssFeed::find($feedId);
            if ($feed) {
                $feed->update(['is_active' => 0]);  // Set feed status to inactive
                Log::warning("Domain not found for RSS Feed ID {$feed->id} (Domain: {$dname}). Marking feed as inactive and skipping.");
            }
            
            // Skip further processing for this feed and return early
            return null; 
        }


        $notification = Notification::create([
            'target_url'        => $data['target_url'],
            'campaign_name'     => $data['campaign_name'],
            'title'             => $data['title'],
            'description'       => $data['description'],
            'banner_image'      => $data['banner_image'] ?? null,
            'banner_icon'       => $data['banner_icon'] ?? null,
            'schedule_type'     => strtolower($data['schedule_type']),
            'message_id'        => \Str::uuid(),
            'btn_1_title'       => $data['btn_1_title'] ?? null,
            'btn_1_url'         => $data['btn_1_url'] ?? null,
            'btn_title_2'       => $data['btn_title_2'] ?? null,
            'btn_url_2'         => $data['btn_url_2'] ?? null,
            'segment_type'      => $data['segment_type'],
            'segment_id'        => $data['segment_id'] ?? null,
        ]);

        $notification->domains()->sync([$domain->id]);
        SendNotificationJob::dispatch($notification->id);

        return $notification;
    }

    private function saveNotificationRecord($rssFeedId, $notificationId, $now)
    {
        RssFeedNotification::updateOrCreate(
            ['rss_feed_id' => $rssFeedId, 'last_sent_at' => $now->toDateString()],
            ['notification_id' => $notificationId, 'last_sent_at' => $now]
        );
    }

    private function shouldSendNotification($lastSentAt, $intervalMin)
    {
        $lastSentTime = Carbon::parse($lastSentAt);
        $nextSendTime = $lastSentTime->addMinutes($intervalMin);

        return Carbon::now()->greaterThanOrEqualTo($nextSendTime);
    }
}
