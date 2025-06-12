<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscriptionPayload extends Model
{
    public $timestamps = false;
    protected $table = 'push_subscriptions_payload';
    protected $primaryKey = 'head_id';
    public $incrementing = false;

    protected $fillable = ['head_id', 'endpoint', 'auth', 'p256dh'];

    public function head()
    {
        return $this->belongsTo(PushSubscriptionHead::class, 'head_id');
    }
}
