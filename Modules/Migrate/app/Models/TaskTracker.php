<?php

namespace Modules\Migrate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskTracker extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_name',
        'file_path',
        'status',
        'message',
        'started_at',
        'completed_at',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
}
