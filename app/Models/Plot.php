<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Plot extends Model
{
    protected $connection = 'mongodb';
    protected $fillable = ['plot_number','area','lease_duration_years','project_type','required_investment','employment_commitment','construction_deadline','financial_conditions','status'];

    protected $casts = [
        'area' => 'float',
        'lease_duration_years' => 'integer',
        'required_investment' => 'float',
        'employment_commitment' => 'integer',
        'construction_deadline' => 'date',
    ];
}
