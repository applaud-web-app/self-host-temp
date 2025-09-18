<?php

namespace Modules\NewsHub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\NewsHub\Database\Factories\NewsBottomSliderFactory;

class NewsBottomSlider extends Model
{
    use HasFactory;

    protected $table = 'news_bottom_sliders';

    protected $fillable = [
        'domain_id','feed_url','theme_color',
        'mode','posts_count','enable_desktop','enable_mobile','status'
    ];
}
