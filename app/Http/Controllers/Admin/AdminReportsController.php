<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentEventType;
use App\Enums\ReportOutcome;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminReportIndexRequest;
use App\Models\Report;
use Illuminate\Http\Request;
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
            ->orderByRaw("CASE status WHEN 'open' THEN 0 WHEN 'under_review' THEN 1 ELSE 2 END")
            ->orderBy('created_at')
            ->paginate(15)->withQueryString()
            ->through(fn (Report $report) => [
                'id' => $report->id,
                'type' => $report->type->value,
                'typeLabel' => $report->type->label(),
                'status' => $report->status->value,
                'statusLabel' => $report->status->label(),
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
            'reporter:id,name,user_type,verification_status', 'reportedUser:id,name,user_type,verification_status',
            'task:id,title,status', 'agreement.currentVersion', 'agreement.paymentEvents.actor:id,name',
            'agreement.completion', 'statements.user:id,name', 'evidence',
        ]);
        $agreement = $report->agreement;
        $version = $agreement?->currentVersion;

        return Inertia::render('admin/reports/show', [
            'report' => [
                'id' => $report->id, 'type' => $report->type->value, 'typeLabel' => $report->type->label(),
                'status' => $report->status->value, 'statusLabel' => $report->status->label(),
                'outcome' => $report->outcome?->value, 'outcomeLabel' => $report->outcome?->label(),
                'adminNote' => $report->admin_note, 'description' => $report->description,
                'createdAt' => $report->created_at?->toDateTimeString(),
                'reporter' => ['id' => $report->reporter_id, 'name' => $report->reporter->name, 'type' => $this->typeLabel($report->reporter), 'verificationStatus' => $report->reporter->verification_status->value],
                'reportedUser' => ['id' => $report->reported_user_id, 'name' => $report->reportedUser->name, 'type' => $this->typeLabel($report->reportedUser), 'verificationStatus' => $report->reportedUser->verification_status->value],
                'task' => ['id' => $report->task_id, 'title' => $report->task->title],
                'agreement' => $agreement ? [
                    'id' => $agreement->id,
                    'finalAmount' => $version?->final_amount,
                    'paymentArrangement' => $version?->payment_arrangement,
                    'dueDate' => $version?->due_date?->toDateString(),
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
                    'amountMismatch' => $event->amount !== null && (float) $event->amount !== (float) ($version?->final_amount ?? 0),
                ])->values() ?? [],
                'statements' => $report->statements->map(fn ($statement) => ['id' => $statement->id, 'name' => $statement->user->name, 'role' => $statement->role, 'body' => $statement->body, 'createdAt' => $statement->created_at?->toDateTimeString()])->values(),
                'evidence' => $report->evidence->map(fn ($evidence) => ['id' => $evidence->id, 'name' => $evidence->original_name, 'mime' => $evidence->mime, 'sizeBytes' => $evidence->size_bytes, 'downloadUrl' => route('reports.evidence', [$report, $evidence])])->values(),
            ],
            'canReview' => request()->user()->can('review', $report),
            'admin' => true,
            'notice' => 'TaskMate records completion and payment status only. Payments are made directly between the poster and the student. TaskMate does not hold, process, recover, or guarantee payment. This review records the information provided and is not a ruling on repayment.',
        ]);
    }

    private function typeLabel(\App\Models\User $user): string
    {
        return config('taskmate.user_types.'.$user->user_type->value, $user->user_type->value);
    }
}
