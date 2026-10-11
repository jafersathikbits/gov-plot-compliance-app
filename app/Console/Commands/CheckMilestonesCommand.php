<?php

namespace App\Console\Commands;

use App\Models\AllotmentCase;
use App\Models\CaseMilestone;
use App\Models\PolicyTemplate;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CaseLifecycleService;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckMilestonesCommand extends Command
{
    protected $signature = 'compliance:check-milestones';
    protected $description = 'Check milestone due dates and update warning / overdue statuses';

    private const DEFAULT_WARNING_DAYS = 7;

    public function handle(NotificationService $notificationService, CaseLifecycleService $caseLifecycleService): int
    {
        $this->info('Checking milestones...');

        // Load everything up front instead of one query per milestone.
        $cases = AllotmentCase::where('is_archived', '!=', true)
            ->where('status', '!=', 'terminated')
            ->get();

        if ($cases->isEmpty()) {
            $this->info('No open cases.');
            return self::SUCCESS;
        }

        $caseIds = $cases->map(fn ($c) => (string) $c->_id)->all();
        $milestonesByCase = CaseMilestone::whereIn('allotment_case_id', $caseIds)->get()->groupBy('allotment_case_id');
        $templates = PolicyTemplate::whereIn('_id', $cases->pluck('policy_template_id')->filter()->unique()->values()->all())
            ->get()
            ->keyBy(fn ($t) => (string) $t->_id);
        $userIds = $cases->flatMap(fn ($c) => [$c->allottee_user_id, $c->district_officer_id])->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $users = User::whereIn('id', $userIds)->get()->keyBy(fn ($u) => (string) $u->id);

        $warnings = 0;
        $overdue = 0;

        foreach ($cases as $case) {
            $caseId = (string) $case->_id;
            $milestones = $milestonesByCase->get($caseId, collect());
            $warningDays = (int) ($templates->get((string) $case->policy_template_id)?->rules['warning_days'] ?? self::DEFAULT_WARNING_DAYS);

            foreach ($milestones as $milestone) {
                if ($milestone->status === 'verified' || ! $milestone->due_date) {
                    continue;
                }

                $dueDate = $milestone->due_date;

                if ($dueDate->isPast()) {
                    $milestone->status = 'overdue';
                    // Alert once per milestone, not every day.
                    if (! $milestone->overdue_alert_sent_at) {
                        $milestone->overdue_alert_sent_at = now();
                        $notificationService->sendCaseAlert($case, 'Red Alert: milestone overdue', 'Milestone '.$milestone->title.' is overdue (due '.$dueDate->format('d M Y').').', $users->get((string) $case->district_officer_id));
                        AuditService::log($caseId, 'milestone_overdue', 'case_milestone', (string) $milestone->_id, ['title' => $milestone->title]);
                        $overdue++;
                    }
                } elseif (now()->diffInDays($dueDate) <= $warningDays) {
                    $milestone->status = 'due_soon';
                    if (! $milestone->warning_sent_at) {
                        $milestone->warning_sent_at = now();
                        $notificationService->sendCaseAlert($case, 'Yellow Alert: milestone due soon', 'Milestone '.$milestone->title.' is due on '.$dueDate->format('d M Y').' (within '.$warningDays.' days).', $users->get((string) $case->allottee_user_id));
                        AuditService::log($caseId, 'milestone_due_soon', 'case_milestone', (string) $milestone->_id, ['title' => $milestone->title]);
                        $warnings++;
                    }
                } else {
                    $milestone->status = 'pending';
                }

                if ($milestone->isDirty()) {
                    $milestone->save();
                }
            }

            $caseLifecycleService->updateCaseStatus($case, $milestones);
        }

        $this->info("Milestone monitoring complete. {$warnings} warning(s), {$overdue} overdue alert(s) sent.");
        return self::SUCCESS;
    }
}
