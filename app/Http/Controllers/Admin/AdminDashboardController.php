<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\User;
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
            ],
            'recentActions' => $recentActions,
        ]);
    }
}
