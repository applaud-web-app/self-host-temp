<?php

namespace Modules\NewsHub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NewsRoll extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'feed_url',
        'title',
        'icon',
        'theme_color',
        'widget_placement',
        'show_on_desktop',
        'show_on_mobile',
        'status',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
