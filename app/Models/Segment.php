<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Segment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'domain',
        'type', 
        'status'
    ];

    public function deviceRules()
    {
        return $this->hasMany(SegmentDeviceRule::class);
    }

    public function geoRules()
    {
        return $this->hasMany(SegmentGeoRule::class);
    }

    public function scopeForDomain(Builder $q, string $domain): Builder
    {
        return $q->where('domain', $domain);
    }
    
}
