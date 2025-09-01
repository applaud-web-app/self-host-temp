<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
   protected $fillable = ['name','status'];

   public function subscriptions()
   {
      return $this->hasMany(PushSubscriptionHead::class, 'parent_origin', 'name');
   }

   public function license()
   {
      // domain_licenses.domain_id → domains.id
      return $this->hasOne(DomainLicense::class, 'domain_id', 'id');
   }

   public function subscriptionSummary()
   {
      return $this->hasOne(DomainSubscriptionSummary::class);
   }
   
}
