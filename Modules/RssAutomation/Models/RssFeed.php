<?php 

namespace Modules\RssAutomation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RssFeed extends Model
{
    use HasFactory;

    protected $table = 'rss_feeds';

    protected $fillable = [
        'name',
        'url',
        'type',
        'random_count',
        'start_time',
        'end_time',
        'interval_min',
        'icon',
        'cta_enabled',
        'button1_title',
        'button1_url',
        'button2_title',
        'button2_url',
        'is_active',
    ];

    public function notifications()
    {
        return $this->hasMany(RssFeedNotification::class, 'rss_feed_id');
    }
}
