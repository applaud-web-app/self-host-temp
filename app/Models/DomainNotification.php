<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainNotification extends Model
{
    protected $fillable = [
        'notification_id',
        'domain_id',
    ];
}
