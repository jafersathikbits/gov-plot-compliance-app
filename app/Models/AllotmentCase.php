<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AllotmentCase extends Model
{
    public const STATUSES = ['active', 'at_risk', 'non_compliant', 'fully_compliant', 'terminated'];

    protected $connection = 'mongodb';
    protected $fillable = ['plot_id','allottee_user_id','policy_template_id','title','status','investment_required','employment_required','construction_deadline','subsidy_amount','caution_deposit_amount','documents','district_officer_id','inspection_officer_id','timeline_generated_at','activated_at','closed_at','legal_status','legal_remarks','is_archived'];

    protected $casts = [
        'construction_deadline' => 'date',
        'timeline_generated_at' => 'datetime',
        'activated_at' => 'datetime',
        'closed_at' => 'datetime',
        'investment_required' => 'float',
        'employment_required' => 'integer',
        'subsidy_amount' => 'float',
        'caution_deposit_amount' => 'float',
        'is_archived' => 'boolean',
    ];

    /**
     * Allottees may only see their own cases; officers and admins see all.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasRole('allottee') && ! $user->hasAnyRole(['super_admin', 'state_admin', 'district_officer', 'inspection_officer'])) {
            $query->where('allottee_user_id', (string) $user->id);
        }

        return $query;
    }

    public function isVisibleTo(User $user): bool
    {
        if ($user->hasAnyRole(['super_admin', 'state_admin', 'district_officer', 'inspection_officer'])) {
            return true;
        }

        return (string) $this->allottee_user_id === (string) $user->id;
    }

    public function isClosed(): bool
    {
        return $this->status === 'terminated' || $this->legal_status === 'terminated' || (bool) $this->is_archived;
    }
}
