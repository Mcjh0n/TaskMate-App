<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function view(User $user, Report $report): bool
    {
        return $user->isAdmin() || in_array($user->id, [$report->reporter_id, $report->reported_user_id], true);
    }

    public function reply(User $user, Report $report): bool
    {
        return $report->reported_user_id === $user->id
            && in_array($report->status, [ReportStatus::Open, ReportStatus::UnderReview], true);
    }

    public function review(User $user, Report $report): bool
    {
        return $user->isAdmin()
            && ! in_array($user->id, [$report->reporter_id, $report->reported_user_id], true)
            && in_array($report->status, [ReportStatus::Open, ReportStatus::UnderReview], true);
    }
}
