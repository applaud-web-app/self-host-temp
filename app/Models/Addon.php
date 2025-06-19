<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    // Which table this model represents (optional if it matches “addons”)
    protected $table = 'addons';

    // Mass‐assignable fields
    protected $fillable = [
        'name',
        'version',
        'file_path',
        'file_size',
        'status',
    ];

    // Cast file_size to integer
    protected $casts = [
        'file_size' => 'integer',
    ];
}
