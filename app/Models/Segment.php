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

    public function toAudienceQuery(): Builder
    {
        $q = Subscriber::query(); 

        if (in_array($this->type, ['device', 'mixed'])) {
            $devices = $this->deviceRules->pluck('device_type');
            $q->whereIn('device', $devices);
        }

        if (in_array($this->type, ['geo', 'mixed'])) {
            foreach ($this->geoRules as $rule) {
                $countryCol = 'country';
                $stateCol   = 'state';

                if ($rule->operator === 'equals') {
                    $q->where($countryCol, $rule->country);
                    if ($rule->state) {
                        $q->where($stateCol, $rule->state);
                    }
                } else {
                    $q->where($countryCol, '!=', $rule->country);
                    if ($rule->state) {
                        $q->where($stateCol, '!=', $rule->state);
                    }
                }
            }
        }

        return $q;
    }
    
    /* ---------- scopes ---------- */
    public function scopeActive($q)
    {
        return $q->where('status', true);
    }

    public function scopePaused($q)
    {
        return $q->where('status', false);
    }
}
