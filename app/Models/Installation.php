<?php

// app/Models/Installation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installation extends Model
{
    protected $fillable = ['is_installed','data'];
    
    public static function instance()
    {
        return static::first() ?? static::create();
    }
}
