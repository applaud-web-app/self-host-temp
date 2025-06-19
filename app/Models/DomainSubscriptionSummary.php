<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainSubscriptionSummary extends Model
{
    protected $fillable = [
        'domain_id',
        'stat_date',
        'total_subscribers',
        'monthly_subscribers',
    ];
    protected $dates = ['stat_date'];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
