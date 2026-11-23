<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Models\Agreement;
use App\Models\Application;
use App\Models\Report;
use App\Models\Task;
use App\Models\User;

class ReportPolicy
{
    public function view(User $user, Report $report): bool
    {
        if ($user->isAdmin() || $report->reporter_id === $user->id) {
            return true;
        }

        return $report->reported_user_id === $user->id && $this->mayRespond($user, $report);
    }

    public function reply(User $user, Report $report): bool
    {
        return ($report->reporter_id === $user->id || ($report->reported_user_id === $user->id && $this->mayRespond($user, $report)))
            && in_array($report->status, [ReportStatus::Open, ReportStatus::UnderReview], true);
    }

    public function review(User $user, Report $report): bool
    {
        return $user->isAdmin()
            && ! in_array($user->id, [$report->reporter_id, $report->reported_user_id], true)
            && in_array($report->status, [ReportStatus::Open, ReportStatus::UnderReview], true);
    }

    private function mayRespond(User $user, Report $report): bool
    {
        if (! (bool) config('taskmate.reports.types.'.$report->type->value.'.respondent_can_reply', false)) {
            return false;
        }
        if ($report->agreement_id) {
            $agreement = Agreement::find($report->agreement_id);
            if ($agreement && in_array($user->id, [$agreement->poster_id, $agreement->student_id], true)) {
                return true;
            }
        }
        if ($report->task_id) {
            $task = Task::find($report->task_id);
            if ($task && ($task->poster_id === $user->id || Application::query()->where('task_id', $task->id)->where('applicant_id', $user->id)->exists())) {
                return true;
            }
        }

        return false;
    }
}
