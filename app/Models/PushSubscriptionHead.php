<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscriptionHead extends Model
{
    protected $table = 'push_subscriptions_head';
    protected $fillable = ['token', 'domain','parent_origin','status'];

    /**
     * Owning domain.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }

    /**
     * Payload record.
     */
    public function payload()
    {
        return $this->hasOne(PushSubscriptionPayload::class, 'head_id');
    }

    /**
     * Meta record.
     */
    public function meta()
    {
        return $this->hasOne(PushSubscriptionMeta::class, 'head_id');
    }
}
