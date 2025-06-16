<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushEventCount extends Model
{
    protected $table = 'push_event_counts';
    public $timestamps = false;
    protected $fillable = [
        'message_id',
        'domain',
        'event',
        'count',
    ];
}
