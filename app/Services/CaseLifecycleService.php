<?php

namespace App\Services;

use App\Models\AllotmentCase;
use App\Models\CaseMilestone;
use App\Models\PolicyTemplate;
use App\Models\Submission;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CaseLifecycleService
{
    public function generateMilestones(AllotmentCase $case, PolicyTemplate $template): void
    {
        $rules = $template->milestones ?? [];
        $startDate = Carbon::parse($case->activated_at ?? now());
        $caseId = (string) $case->_id;

        foreach ($rules as $index => $rule) {
            CaseMilestone::create([
                'allotment_case_id' => $caseId,
                'type' => $rule['type'] ?? 'generic',
                'title' => $rule['title'] ?? 'Milestone '.($index + 1),
                'description' => $rule['description'] ?? null,
                'due_date' => $startDate->copy()->addMonths((int) ($rule['due_in_months'] ?? 0)),
                'required_value' => (float) ($rule['required_value'] ?? 0),
                'current_value' => 0,
                'unit' => $rule['unit'] ?? 'count',
                'status' => 'pending',
                'evidence_required' => $rule['evidence_required'] ?? true,
            ]);
        }

        $case->timeline_generated_at = now();
        $case->save();

        AuditService::log($caseId, 'timeline_generated', 'allotment_case', $caseId, [
            'template' => $template->name,
        ]);
    }

    /**
     * Recalculate the case status from its milestones. Saves and audits only when something changed,
     * so it is safe to call repeatedly (e.g. from the daily scheduler).
     */
    public function updateCaseStatus(AllotmentCase $case, ?Collection $milestones = null): void
    {
        $milestones ??= CaseMilestone::where('allotment_case_id', (string) $case->_id)->get();
        $previous = $case->status;

        if ($case->legal_status === 'terminated') {
            $status = 'terminated';
        } elseif ($milestones->isNotEmpty() && $milestones->every(fn ($m) => $m->status === 'verified')) {
            $status = 'fully_compliant';
        } elseif ($milestones->contains(fn ($m) => $m->status === 'overdue')) {
            $status = 'non_compliant';
        } elseif ($milestones->contains(fn ($m) => $m->status === 'due_soon')) {
            $status = 'at_risk';
        } else {
            $status = 'active';
        }

        $case->status = $status;
        if (in_array($status, ['fully_compliant', 'terminated'], true) && ! $case->closed_at) {
            $case->closed_at = now();
        }

        if (! $case->isDirty()) {
            return;
        }

        $case->save();

        if ($status !== $previous) {
            AuditService::log((string) $case->_id, 'case_status_updated', 'allotment_case', (string) $case->_id, [
                'from' => $previous,
                'status' => $status,
            ]);
        }
    }

    /**
     * Apply an accepted submission's reported progress to the case milestones.
     *
     * @return int number of milestones newly verified
     */
    public function applySubmission(Submission $submission, Collection $milestones, string $reviewerId): int
    {
        $progress = $this->constructionProgress($submission);
        $verified = 0;

        foreach ($milestones as $milestone) {
            if ($milestone->status === 'verified') {
                continue;
            }

            $current = (float) $milestone->current_value;

            $current = match ($milestone->type) {
                'investment' => max($current, (float) $submission->investment_spent),
                'employment' => max($current, (float) $submission->employees_hired),
                'construction' => max($current, $milestone->unit === 'percent'
                    ? $progress
                    : ($progress >= 100 ? (float) $milestone->required_value : $current)),
                'time_bound' => $progress > 0 ? max($current, (float) $milestone->required_value) : $current,
                default => $current,
            };

            $milestone->current_value = $current;

            if ($current >= (float) $milestone->required_value) {
                $milestone->status = 'verified';
                $milestone->verified_at = now();
                $milestone->verified_by = $reviewerId;
                $verified++;
            }

            if ($milestone->isDirty()) {
                $milestone->save();
            }
        }

        return $verified;
    }

    public function verifyEligibleForClaim(AllotmentCase $case): bool
    {
        return in_array($case->status, ['fully_compliant', 'active', 'at_risk'], true)
            && CaseMilestone::where('allotment_case_id', (string) $case->_id)->where('status', 'verified')->exists();
    }

    /**
     * Construction progress in percent. Uses the structured fields when present and falls back to
     * parsing free-text status from older submissions ("Construction complete" but not "incomplete").
     */
    private function constructionProgress(Submission $submission): float
    {
        $status = strtolower(trim((string) $submission->construction_status));

        if ($status === 'completed') {
            return 100.0;
        }

        if ($submission->construction_progress !== null) {
            return (float) max(0, min(100, $submission->construction_progress));
        }

        if ($status === '' || $status === 'not_started' || str_contains($status, 'not started')) {
            return 0.0;
        }

        $saysComplete = preg_match('/\bcomplet(e|ed)\b/', $status) === 1;
        $negated = preg_match('/\b(not|partially|partly|almost|nearly)\b[\w\s-]*\bcomplet/', $status) === 1;

        return $saysComplete && ! $negated && ! str_contains($status, 'foundation') ? 100.0 : 1.0;
    }
}
