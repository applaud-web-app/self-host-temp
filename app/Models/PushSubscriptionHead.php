<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscriptionHead extends Model
{
    protected $table = 'push_subscriptions_head';
    protected $fillable = ['token', 'domain'];

    public function payload()
    {
        return $this->hasOne(PushSubscriptionPayload::class, 'head_id');
    }

    public function meta()
    {
        return $this->hasOne(PushSubscriptionMeta::class, 'head_id');
    }
}
