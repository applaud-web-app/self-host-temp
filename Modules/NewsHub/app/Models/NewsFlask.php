<?php

namespace Modules\NewsHub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\NewsHub\Database\Factories\NewsFlaskFactory;

class NewsFlask extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'feed_url',
        'title',
        'theme_color',
        'trigger_timing',
        'after_seconds',
        'show_again_after_minutes',
        'enable_desktop',
        'enable_mobile',
        'status',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
