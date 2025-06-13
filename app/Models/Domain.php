<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DomainNotification;

class Domain extends Model
{
   protected $fillable = ['name','status'];

   /**
    * Notifications associated with this domain.
    */
   public function notifications()
   {
      return $this->belongsToMany(Notification::class, 'domain_notification')
                ->using(DomainNotification::class);
   }

   /**
    * Subscribers under this domain.
    */
   public function subscriptions()
   {
      return $this->hasMany(PushSubscriptionHead::class, 'domain_id');
   }
   
}
