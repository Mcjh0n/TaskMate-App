<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminReportIndexRequest;
use App\Models\Report;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminReportsController extends Controller
{
    public function index(AdminReportIndexRequest $request): Response
    {
        $filters = $request->validated();
        $reports = Report::query()
            ->with(['reporter:id,name', 'reportedUser:id,name', 'task:id,title'])
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(fn ($query) => $query
                ->whereHas('reporter', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                ->orWhereHas('reportedUser', fn ($user) => $user->where('name', 'like', "%{$search}%"))))
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($query, string $priority) => $query->where('priority', $priority))
            ->when(isset($filters['escalated']), fn ($query) => $filters['escalated'] === '1' ? $query->whereNotNull('escalated_at') : $query->whereNull('escalated_at'))
            ->when(isset($filters['has_evidence']), fn ($query) => $filters['has_evidence'] === '1' ? $query->whereHas('evidence') : $query->whereDoesntHave('evidence'))
            ->orderByRaw("CASE priority WHEN 'high' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE status WHEN 'open' THEN 0 WHEN 'under_review' THEN 1 ELSE 2 END")
            ->orderBy('created_at')
            ->paginate(15)->withQueryString()
            ->through(fn (Report $report) => [
                'id' => $report->id,
                'type' => $report->type->value,
                'typeLabel' => $report->type->label(),
                'status' => $report->status->value,
                'statusLabel' => $report->status->label(),
                'priority' => $report->priority,
                'escalated' => $report->escalated_at !== null,
                'reporterName' => $report->reporter->name,
                'reportedUserName' => $report->reportedUser->name,
                'taskTitle' => $report->task->title,
                'createdAt' => $report->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('admin/reports/index', [
            'reports' => $reports,
            'filters' => $filters,
            'types' => collect(ReportType::cases())->map(fn (ReportType $item) => ['value' => $item->value, 'label' => $item->label()])->values(),
            'statuses' => collect(ReportStatus::cases())->map(fn (ReportStatus $item) => ['value' => $item->value, 'label' => $item->label()])->values(),
        ]);
    }

    public function show(Report $report): Response
    {
        $report->load([
            'reporter:id,name,user_type,verification_status', 'reporter.sanctions', 'reportedUser:id,name,user_type,verification_status', 'reportedUser.sanctions',
            'task.poster:id,name,user_type,verification_status', 'task.applications.applicant:id,name,user_type,verification_status', 'task.contentFlags', 'task:id,title,status,poster_id,category,description,required_activities,deliverables,skill_requirements,complexity,budget,budget_min,budget_max,work_mode,workplace_name,workplace_address,physical_demand,academic_involvement,hazard_exposure,hazard_description,private_meeting_required,workplace_setting', 'agreement.currentVersion', 'agreement.versions.proposer:id,name', 'agreement.versions.confirmations.user:id,name', 'agreement.paymentEvents.actor:id,name',
            'agreement.completion', 'statements.user:id,name', 'evidence', 'messageSnapshots',
        ]);
        $agreement = $report->agreement;
        $version = $agreement?->currentVersion;

        return Inertia::render('admin/reports/show', [
            'report' => [
                'id' => $report->id, 'type' => $report->type->value, 'typeLabel' => $report->type->label(),
                'status' => $report->status->value, 'statusLabel' => $report->status->label(),
                'outcome' => $report->outcome?->value, 'outcomeLabel' => $report->outcome?->label(),
                'adminNote' => $report->admin_note, 'description' => $report->description,
                'resolutionMessage' => $report->resolution_message, 'priority' => $report->priority,
                'escalatedAt' => $report->escalated_at?->toDateTimeString(), 'escalationNote' => $report->escalation_note,
                'escalationOfficeLabel' => config('taskmate.moderation.escalation_office_label'),
                'createdAt' => $report->created_at?->toDateTimeString(),
                'reporter' => ['id' => $report->reporter_id, 'name' => $report->reporter->name, 'type' => $this->typeLabel($report->reporter), 'verificationStatus' => $report->reporter->verification_status->value],
                'reportedUser' => ['id' => $report->reported_user_id, 'name' => $report->reportedUser->name, 'type' => $this->typeLabel($report->reportedUser), 'verificationStatus' => $report->reportedUser->verification_status->value],
                'reporterSanctions' => $this->sanctions($report->reporter),
                'reportedUserSanctions' => $this->sanctions($report->reportedUser),
                'reporterPriorReports' => $report->reporter->filedReports()->count(),
                'reportedUserPriorReports' => $report->reportedUser->receivedReports()->count(),
                'task' => ['id' => $report->task_id, 'title' => $report->task->title, 'status' => $report->task->status->value, 'posterName' => $report->task->poster?->name, 'category' => $report->task->category, 'description' => $report->task->description, 'requiredActivities' => $report->task->required_activities, 'deliverables' => $report->task->deliverables, 'skillRequirements' => $report->task->skill_requirements, 'complexity' => $report->task->complexity, 'budget' => $report->task->budget, 'budgetDisplay' => $report->task->budgetDisplay(), 'workMode' => config('taskmate.work_modes.'.$report->task->work_mode, config('taskmate.legacy_work_modes.'.$report->task->work_mode, $report->task->work_mode)), 'workplaceName' => $report->task->workplace_name, 'workplaceAddress' => $report->task->workplace_address, 'physicalDemand' => $report->task->physical_demand, 'academicInvolvement' => $report->task->academic_involvement, 'hazardExposure' => $report->task->hazard_exposure, 'hazardDescription' => $report->task->hazard_description, 'privateMeetingRequired' => $report->task->private_meeting_required, 'workplaceSetting' => $report->task->workplace_setting, 'flags' => $report->task->contentFlags->map(fn ($flag) => ['id' => $flag->id, 'category' => $flag->category, 'matched' => $flag->matched, 'field' => $flag->field, 'status' => $flag->status->value])->values(), 'applications' => $report->task->applications->map(fn ($application) => ['applicantName' => $application->applicant->name, 'status' => $application->status->value, 'createdAt' => $application->created_at?->toDateTimeString()])->values()],
                'agreement' => $agreement ? [
                    'id' => $agreement->id,
                    'finalAmount' => $version?->final_amount,
                    'paymentArrangement' => $version?->payment_arrangement,
                    'durationDays' => $version?->duration_days,
                    'recordReference' => $version?->content_hash,
                ] : null,
                'completion' => $agreement?->completion ? [
                    'deliveredAt' => $agreement->completion->delivered_at?->toDateTimeString(),
                    'deliveredNote' => $agreement->completion->delivered_note,
                    'completedAt' => $agreement->completion->completed_at?->toDateTimeString(),
                    'completionNote' => $agreement->completion->completion_note,
                ] : null,
                'paymentEvents' => $agreement?->paymentEvents->map(fn ($event) => [
                    'id' => $event->id, 'event' => $event->event->value, 'actorName' => $event->actor->name,
                    'amount' => $event->amount, 'paidOn' => $event->paid_on?->toDateString(), 'method' => $event->method,
                    'note' => $event->note, 'createdAt' => $event->created_at?->toDateTimeString(),
                    'amountMismatch' => $event->amount !== null && (float) $event->amount !== (float) ($version->final_amount ?? 0),
                ])->values() ?? [],
                'statements' => $report->statements->map(fn ($statement) => ['id' => $statement->id, 'name' => $statement->user->name, 'role' => $statement->role, 'body' => $statement->body, 'createdAt' => $statement->created_at?->toDateTimeString()])->values(),
                'versions' => $agreement?->versions->map(fn ($item) => ['version' => $item->version_number, 'proposer' => $item->proposer->name, 'deliverables' => $item->deliverables, 'terms' => $item->terms, 'finalAmount' => $item->final_amount, 'startDate' => $item->start_date?->toDateString(), 'durationDays' => $item->duration_days, 'workMode' => $item->work_mode, 'workplaceName' => $item->workplace_name, 'workplaceAddress' => $item->workplace_address, 'paymentArrangement' => $item->payment_arrangement, 'comment' => $item->comment, 'hash' => $item->content_hash, 'confirmations' => $item->confirmations->map(fn ($confirmation) => ['name' => $confirmation->user->name, 'at' => $confirmation->confirmed_at?->toDateTimeString()])->values()])->values() ?? [],
                'messageSnapshots' => $report->messageSnapshots->map(fn ($snapshot) => [
                    'sender' => $snapshot->sender_display_name,
                    'body' => $snapshot->body,
                    'sentAt' => $snapshot->sent_at?->toDateTimeString(),
                    'isReportedMessage' => $snapshot->is_reported_message,
                ])->values(),
                'evidence' => $report->evidence->map(fn ($evidence) => ['id' => $evidence->id, 'name' => $evidence->original_name, 'mime' => $evidence->mime, 'sizeBytes' => $evidence->size_bytes, 'downloadUrl' => route('reports.evidence', [$report, $evidence])])->values(),
            ],
            'canReview' => request()->user()->can('review', $report),
            'types' => collect(ReportType::cases())->map(fn (ReportType $item) => ['value' => $item->value, 'label' => $item->label()])->values(),
            'admin' => true,
            'notice' => 'TaskMate records completion and payment status only. Payments are made directly between the poster and the student. TaskMate does not hold, process, recover, or guarantee payment. This review records the information provided and is not a ruling on repayment.',
        ]);
    }

    private function typeLabel(User $user): string
    {
        return config('taskmate.user_types.'.$user->user_type->value, $user->user_type->value);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sanctions(User $user): array
    {
        return $user->sanctions->map(fn ($sanction) => ['type' => $sanction->type->value, 'reason' => $sanction->reason, 'active' => ! $sanction->lifted_at && $sanction->starts_at?->isPast() && ($sanction->ends_at === null || $sanction->ends_at->isFuture())])->values()->all();
    }
}
