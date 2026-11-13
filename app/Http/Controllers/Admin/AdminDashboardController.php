<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\User;
use App\Models\Task;
use App\Enums\TaskStatus;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\ContentFlag;
use App\Models\UserSanction;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $recentActions = AdminAction::query()
            ->with(['admin:id,name', 'subject'])
            ->latest('created_at')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (AdminAction $action) => [
                'id' => $action->id,
                'action' => $action->action,
                'adminName' => $action->admin?->name,
                'subjectName' => $action->subject instanceof User
                    ? $action->subject->name
                    : class_basename($action->subject_type).' #'.$action->subject_id,
                'note' => $action->note,
                'createdAt' => $action->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('admin/dashboard', [
            'counts' => [
                'pending' => User::query()->where('verification_status', VerificationStatus::Pending->value)->count(),
                'moreInfoRequested' => User::query()->where('verification_status', VerificationStatus::MoreInfoRequested->value)->count(),
                'verified' => User::query()->where('verification_status', VerificationStatus::Verified->value)->count(),
                'rejected' => User::query()->where('verification_status', VerificationStatus::Rejected->value)->count(),
                'tasksAwaitingReview' => Task::query()->where('status', TaskStatus::PendingReview->value)->count(),
                'highRiskTasksAwaitingReview' => Task::query()->where('status', TaskStatus::PendingReview->value)->where('risk_level', 'high')->count(),
                'openReports' => Report::query()->where('status', ReportStatus::Open->value)->count(),
                'reportsUnderReview' => Report::query()->where('status', ReportStatus::UnderReview->value)->count(),
                'highPriorityReports' => Report::query()->where('priority', 'high')->whereIn('status', [ReportStatus::Open->value, ReportStatus::UnderReview->value])->count(),
                'escalatedReports' => Report::query()->whereNotNull('escalated_at')->whereIn('status', [ReportStatus::Open->value, ReportStatus::UnderReview->value])->count(),
                'openFlags' => ContentFlag::query()->where('status', 'open')->count(),
                'activeSuspensions' => UserSanction::query()->where('type', 'suspension')->whereNull('lifted_at')->where('starts_at', '<=', now())->where('ends_at', '>', now())->count(),
            ],
            'recentActions' => $recentActions,
        ]);
    }
}
