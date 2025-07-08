<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscriptionMeta extends Model
{
    public $timestamps = false;
    protected $table = 'push_subscriptions_meta';
    protected $primaryKey = 'head_id';
    public $incrementing = false;

    protected $fillable = [
        'head_id', 'ip_address', 'country', 'state', 'city',
        'device', 'browser', 'platform', 'subscribed_url'
    ];

    public function head()
    {
        return $this->belongsTo(PushSubscriptionHead::class, 'head_id');
    }
}
