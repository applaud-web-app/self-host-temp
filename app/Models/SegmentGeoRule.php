<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SegmentGeoRule extends Model
{
    use HasFactory;
    public $timestamps   = false;

    protected $fillable = [
        'segment_id',
        'operator',
        'country',
        'state',
    ];

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }
}
