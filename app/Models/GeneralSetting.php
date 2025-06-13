<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    // point at your non-plural table
    protected $table = 'general_setting';

    protected $fillable = [
        'site_name',
        'site_url',
        'site_tagline',
    ];
}
