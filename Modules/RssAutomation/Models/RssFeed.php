<?php 

namespace Modules\RssAutomation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RssFeed extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rss_feeds';

    protected $fillable = [
        'rss_feed_name',
        'rss_feed_url',
        'rss_feed_title',
        'rss_feed_description',
        'banner_image',
        'banner_icon',
    ];

    protected $dates = ['deleted_at'];
}
