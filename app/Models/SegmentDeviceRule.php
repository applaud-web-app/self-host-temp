<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SegmentDeviceRule extends Model
{
    use HasFactory;
    
    public $timestamps   = false;
    protected $fillable = [
        'segment_id',
        'device_type',
    ];

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }
}
