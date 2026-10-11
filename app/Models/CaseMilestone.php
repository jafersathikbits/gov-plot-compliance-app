<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class CaseMilestone extends Model
{
    protected $connection = 'mongodb';
    protected $fillable = ['allotment_case_id','type','title','description','due_date','required_value','current_value','unit','status','verified_at','verified_by','warning_sent_at','overdue_alert_sent_at','evidence_required'];

    protected $casts = [
        'due_date' => 'datetime',
        'verified_at' => 'datetime',
        'warning_sent_at' => 'datetime',
        'overdue_alert_sent_at' => 'datetime',
        'required_value' => 'float',
        'current_value' => 'float',
        'evidence_required' => 'boolean',
    ];

    public function progressPercent(): int
    {
        $required = (float) $this->required_value;
        if ($required <= 0) {
            return $this->status === 'verified' ? 100 : 0;
        }

        return (int) min(100, round(((float) $this->current_value / $required) * 100));
    }
}
