<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AuditLog extends Model
{
    protected $connection = 'mongodb';
    protected $fillable = ['allotment_case_id','user_id','action','entity_type','entity_id','meta','logged_at'];

    protected $casts = [
        'logged_at' => 'datetime',
    ];
}
