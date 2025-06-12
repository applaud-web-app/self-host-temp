<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'target_url',
        'campaign_name',
        'title',
        'description',
        'banner_image',
        'banner_icon',
        'schedule_type',
        'one_time_datetime',
        'recurring_start_date',
        'recurring_end_date',
        'occurrence',
        'recurring_start_time',
    ];

    protected $casts = [
        'one_time_datetime'    => 'datetime',
        'recurring_start_date' => 'date',
        'recurring_end_date'   => 'date',
        'recurring_start_time' => 'datetime:H:i',
    ];

    /**
     * The domains this notification is sent to.
     */
    public function domains()
    {
        return $this->belongsToMany(Domain::class, 'domain_notification');
    }
}
