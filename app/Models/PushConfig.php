<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushConfig extends Model
{
    protected $fillable = ['service_account_json','vapid_public_key','vapid_private_key','web_app_config'];

    public function getWebAppConfigAttribute($value): ?array
    {
        return $value ? json_decode(decrypt($value), true) : null;
    }
    
}
