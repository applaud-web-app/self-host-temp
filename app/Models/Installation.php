<?php

// app/Models/Installation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installation extends Model
{
    protected $fillable = ['is_installed','completed_step','license_key', 'licensed_domain'];
    
    public static function instance()
    {
        // Option B: catch the exception
        try {
            return static::first() ?? static::create();
        } catch (QueryException $e) {
            // table doesn’t exist or other DB error
            return new static;
        }
    }
}
