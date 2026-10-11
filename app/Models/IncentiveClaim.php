<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class IncentiveClaim extends Model
{
    public const TYPES = ['subsidy' => 'Land cost subsidy', 'refund' => 'Caution deposit refund'];

    protected $connection = 'mongodb';
    protected $fillable = ['allotment_case_id','type','amount','status','submitted_by','reviewed_by','remarks','payment_reference','approved_at','paid_at'];

    protected $casts = [
        'amount' => 'float',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }
}
