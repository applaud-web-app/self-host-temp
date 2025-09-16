<?php

namespace Modules\AdvanceSegmentation\Models;

use Illuminate\Database\Eloquent\Model;

class SegmentTimeRule extends Model
{
    protected $table = 'segment_time_rules';
    public $timestamps = false;

    protected $fillable = [
        'segment_id', 'start_at', 'end_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
    ];
    
    public function segment()
    {
        return $this->belongsTo(Segment::class, 'segment_id');
    }
}
