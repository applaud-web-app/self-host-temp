<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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

    public static function dailyCleanupEnabled(): bool
    {
        return Cache::remember('settings.daily_cleanup', now()->addMinutes(5), function () {
            return (bool) static::query()->value('daily_cleanup');
        });
    }

    protected static function booted()
    {
        static::saved(fn () => Cache::forget('settings.daily_cleanup'));
    }
}
