<?php

namespace Modules\Migrate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MigrateSubs extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'endpoint',
        'public_key',
        'private_key',
        'auth',
        'p256dh',
        'ip_address',
        'migration_status', // default set pending
        'status' // default set 1
    ];
}
