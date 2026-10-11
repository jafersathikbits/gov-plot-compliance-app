<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Submission extends Model
{
    public const CONSTRUCTION_STAGES = [
        'not_started' => 'Not started',
        'site_preparation' => 'Site preparation',
        'foundation' => 'Foundation in progress',
        'structure' => 'Structure in progress',
        'finishing' => 'Finishing works',
        'completed' => 'Construction complete',
    ];

    protected $connection = 'mongodb';
    protected $fillable = ['allotment_case_id','milestone_id','submitted_by','investment_spent','employees_hired','construction_status','construction_progress','remarks','evidence_files','status','reviewed_by','review_remarks','submitted_at','reviewed_at'];

    protected $casts = [
        'investment_spent' => 'float',
        'employees_hired' => 'integer',
        'construction_progress' => 'float',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function constructionLabel(): string
    {
        return self::CONSTRUCTION_STAGES[$this->construction_status] ?? (string) $this->construction_status;
    }
}
