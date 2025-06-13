<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushConfig extends Model
{
    protected $fillable = ['service_account_json','vapid_public_key','vapid_private_key','web_app_config'];

    /**
    * Return decrypted and decoded service account credentials.
    */
    public function getCredentialsAttribute(): array
    {
        return json_decode(decrypt($this->service_account_json), true) ?: [];
    }
    
    /**
    * Return decrypted web app config.
    */
    public function getWebAppConfigAttribute($value): ?array
    {
        return $value ? json_decode(decrypt($value), true) : null;
    }
    
}
