<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSend extends Model
{
   protected $fillable = ['notification_id','subscription_head_id','status'];
}
