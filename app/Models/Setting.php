<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    protected $fillable = [
        'batch_size',
        'daily_cleanup',
    ];

    protected $casts = [
        'batch_size'    => 'integer',
        'daily_cleanup' => 'boolean',
    ];
}
