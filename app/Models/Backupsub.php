<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Backupsub extends Model
{
    protected $table = 'backupsubs';

    protected $fillable = [
        'filename',
        'count',
        'path',
    ];
}
