<?php

namespace Modules\AdvanceSegmentation\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\UrlCanon;

class SegmentUrlRule extends Model
{
    protected $table = 'segment_url_rules';
    public $timestamps = false;

    protected $fillable = [
        'segment_id', 'url', 'url_hash',
    ];

    public function segment()
    {
        return $this->belongsTo(Segment::class, 'segment_id');
    }
    
}
