<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSend extends Model
{
   protected $fillable = ['notification_id','subscription_head_id','status'];

   /**
    * Corresponding notification.
    */
   public function notification()
   {
      return $this->belongsTo(Notification::class);
   }

   /**
    * Subscription that was targeted.
    */
   public function subscriptionHead()
   {
      return $this->belongsTo(PushSubscriptionHead::class, 'subscription_head_id');
   }
   
}
