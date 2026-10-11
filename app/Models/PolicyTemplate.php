<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class PolicyTemplate extends Model
{
    protected $connection = 'mongodb';
    protected $fillable = ['name','code','description','milestones','rules'];
}
