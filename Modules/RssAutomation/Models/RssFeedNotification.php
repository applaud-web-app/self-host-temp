<?php

namespace Modules\RssAutomation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RssFeedNotification extends Model
{
    use HasFactory;

    // Define the table name if it's different from the default (optional)
    protected $table = 'rss_feed_notifications';

    // Define the fillable fields
    protected $fillable = [
        'rss_feed_id',
        'notification_id',
        'last_sent_at',
    ];

    // Define relationships
    public function rssFeed()
    {
        return $this->belongsTo(RssFeed::class, 'rss_feed_id');
    }

    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }

    public function getFormattedLastSentAtAttribute()
    {
        return $this->last_sent_at ? $this->last_sent_at->format('Y-m-d H:i:s') : null;
    }
}
