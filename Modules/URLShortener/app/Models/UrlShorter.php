<?php

namespace Modules\URLShortener\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\URLShortener\Database\Factories\UrlShorterFactory;

class UrlShorter extends Model
{
    use HasFactory;

    protected $table = 'url_shorter';

    protected $fillable = [
        'domain', // store domain name
        'target_url',
        'short_url',
        'prompt',
        'forced_subscribe',
        'type',
        'status',
    ];
}
