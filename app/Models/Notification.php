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
        'message_id',
        'active_count',
        'success_count',
        'failed_count',
    ];

    protected $casts = [
        'one_time_datetime'    => 'datetime',
    ];

    /**
     * The domains this notification is sent to.
     */
    public function domains()
    {
        return $this->belongsToMany(Domain::class, 'domain_notification');
    }

    /**
     * Send records of this notification.
     */
    public function sends()
    {
        return $this->hasMany(NotificationSend::class);
    }
}
