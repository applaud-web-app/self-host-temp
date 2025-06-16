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
        // CTA fields
        'btn_1_title',
        'btn_1_url',
        'btn_title_2',
        'btn_url_2'
    ];

    protected $casts = [
        'one_time_datetime'    => 'datetime',
    ];

    /**
     * The domains this notification is sent to.
     */
    public function domains()
    {
        return $this->belongsToMany(Domain::class, 'domain_notification')->withPivot('status','sent_at');;
    }

    public function eventCounts()
    {
        // link message_id (not id) to push_event_counts.message_id
        return $this->hasMany(PushEventCount::class, 'message_id', 'message_id');
    }

    /**
     * Send records of this notification.
     */
    public function sends()
    {
        return $this->hasMany(NotificationSend::class);
    }
}
