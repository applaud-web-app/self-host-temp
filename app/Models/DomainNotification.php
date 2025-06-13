<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainNotification extends Model
{
    public $incrementing = false;
    public $timestamps   = false;
    protected $table     = 'domain_notification';
    protected $fillable  = ['notification_id', 'domain_id'];
}
