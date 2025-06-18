<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainLicense extends Model
{
    protected $fillable = [
        'domain_id',
        'salt',
        'key_hash',
        'is_used',
        'used_at'
    ];

    /** Mark this license as used (one-time). */
    public function markUsed(): void
    {
        $this->update([
            'is_used' => true,
            'used_at' => now(),
        ]); 
    }
    
}
