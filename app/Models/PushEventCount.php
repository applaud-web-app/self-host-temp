<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushEventCount extends Model
{
    protected $table = 'push_event_counts';
    public $timestamps = false;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'message_id',
        'event', // 'received', 'click'
        'count',
    ];
}
