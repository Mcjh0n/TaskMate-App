<?php

namespace App\Http\Controllers;

use App\Enums\PaymentEventType;
use App\Enums\ReportStatus;
use App\Http\Requests\StoreNonPaymentReportRequest;
use App\Http\Requests\StoreReportStatementRequest;
use App\Models\Agreement;
use App\Models\Report;
use App\Models\ReportEvidence;
use App\Models\Task;
use App\Services\ReportWorkflow;
use App\Enums\TaskStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function store(StoreNonPaymentReportRequest $request, Agreement $agreement, ReportWorkflow $workflow): RedirectResponse
    {
        $data = $request->validated();
        $report = $workflow->createNonPayment($agreement, $request->user(), $data['description'], $data['evidence'] ?? []);
        return redirect()->route('reports.show', $report)->with('status', 'Your non-payment report was submitted for review.');
    }

    public function show(Report $report, Request $request): Response
    {
        Gate::authorize('view', $report);
        $report->load([
            'reporter:id,name,user_type,verification_status', 'reportedUser:id,name,user_type,verification_status',
            'task:id,title,status', 'agreement.currentVersion', 'agreement.paymentEvents.actor:id,name',
            'agreement.completion', 'statements.user:id,name', 'evidence',
        ]);
        $agreement = $report->agreement;
        $version = $agreement?->currentVersion;
        $events = $agreement?->paymentEvents ?? collect();
        $isReporter = $report->reporter_id === $request->user()->id;
        $isAdmin = $request->user()->isAdmin();
        $canReply = $request->user()->can('reply', $report);
        $task = $report->task;
        $task->loadMissing(['applications.applicant:id,name,user_type,verification_status']);

        return Inertia::render('reports/show', [
            'report' => [
                'id' => $report->id,
                'type' => $report->type->value,
                'typeLabel' => $report->type->label(),
                'status' => $report->status->value,
                'statusLabel' => $report->status->label(),
                'outcome' => $report->outcome?->value,
                'outcomeLabel' => $report->outcome?->label(),
                'adminNote' => null,
                'resolutionMessage' => $isReporter ? $report->resolution_message : null,
                'priority' => $report->priority,
                'description' => $report->description,
                'createdAt' => $report->created_at?->toDateTimeString(),
                'reporter' => $isReporter || $isAdmin ? ['id' => $report->reporter_id, 'name' => $report->reporter->name, 'type' => $this->typeLabel($report->reporter), 'verificationStatus' => $report->reporter->verification_status->value] : ['id' => null, 'name' => 'Reporter', 'type' => null, 'verificationStatus' => null],
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
                'paymentEvents' => $events->map(fn ($event) => [
                    'id' => $event->id, 'event' => $event->event->value, 'actorName' => $event->actor->name,
                    'amount' => $event->amount, 'paidOn' => $event->paid_on?->toDateString(), 'method' => $event->method,
                    'note' => $event->note, 'createdAt' => $event->created_at?->toDateTimeString(),
                    'amountMismatch' => $event->amount !== null && (float) $event->amount !== (float) ($version?->final_amount ?? 0),
                ])->values(),
                'statements' => $report->statements->map(fn ($statement) => ['id' => $statement->id, 'name' => ! $isReporter && ! $isAdmin && $statement->role === 'reporter' ? 'Reporter' : $statement->user->name, 'role' => $statement->role, 'body' => $statement->body, 'createdAt' => $statement->created_at?->toDateTimeString()])->values(),
                'evidence' => $report->evidence->map(fn (ReportEvidence $evidence) => ['id' => $evidence->id, 'name' => $evidence->original_name, 'mime' => $evidence->mime, 'sizeBytes' => $evidence->size_bytes, 'downloadUrl' => route('reports.evidence', [$report, $evidence])])->values(),
                'relatedTask' => $isAdmin ? [
                    'id' => $task->id, 'title' => $task->title, 'status' => $task->status->label(), 'category' => $task->category,
                    'description' => $task->description, 'requiredActivities' => $task->required_activities, 'deliverables' => $task->deliverables,
                    'skillRequirements' => $task->skill_requirements, 'workMode' => $task->work_mode, 'workplaceName' => $task->workplace_name,
                    'workplaceAddress' => $task->workplace_address,
                    'applications' => $task->applications->map(fn ($application) => ['name' => $application->applicant->name, 'status' => $application->status->value])->values(),
                    'flags' => $task->contentFlags()->get()->map(fn ($flag) => ['category' => $flag->category, 'matched' => $flag->matched, 'field' => $flag->field, 'status' => $flag->status->value])->values(),
                ] : null,
            ],
            'viewerId' => $request->user()->id,
            'canReply' => $canReply,
            'status' => $request->session()->get('status'),
            'statementMaxLength' => config('taskmate.reports.statement_max_length', 3000),
            'notice' => $this->notice(),
            'responseTargetDays' => config('taskmate.moderation.response_target_days'),
            'contactLine' => config('taskmate.moderation.sanction_notice_contact'),
        ]);
    }

    public function mine(Request $request): Response
    {
        $replyable = collect(config('taskmate.reports.types', []))->filter(fn ($settings) => $settings['respondent_can_reply'] ?? false)->keys()->all();
        $reports = \App\Models\Report::query()
            ->where(fn ($query) => $query->where('reporter_id', $request->user()->id)->orWhere(fn ($respondent) => $respondent->where('reported_user_id', $request->user()->id)->whereIn('type', $replyable)))
            ->with(['task:id,title', 'reporter:id,name', 'reportedUser:id,name'])
            ->latest()
            ->paginate(12)
            ->through(fn (Report $report) => [
                'id' => $report->id,
                'typeLabel' => $report->type->label(),
                'status' => $report->status->value,
                'statusLabel' => $report->status->label(),
                'taskTitle' => $report->task->title,
                'otherParty' => $request->user()->id === $report->reporter_id ? $report->reportedUser->name : 'Reporter',
                'isReporter' => $request->user()->id === $report->reporter_id,
                'createdAt' => $report->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('reports/index', ['reports' => $reports, 'status' => $request->session()->get('status')]);
    }

    public function reply(StoreReportStatementRequest $request, Report $report, ReportWorkflow $workflow): RedirectResponse
    {
        $data = $request->validated();
        $workflow->addRespondentStatement($report, $request->user(), $data['body'], $data['evidence'] ?? []);
        return back()->with('status', 'Your response was added to the report.');
    }

    public function evidence(Report $report, ReportEvidence $evidence, Request $request)
    {
        Gate::authorize('view', $report);
        abort_unless($evidence->report_id === $report->id, 404);
        abort_unless(Storage::disk('evidence')->exists($evidence->stored_path), 404);
        $safeName = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($evidence->original_name)) ?: 'report-evidence';

        return Storage::disk('evidence')->download($evidence->stored_path, $safeName, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => 'sandbox',
        ]);
    }

    private function typeLabel(\App\Models\User $user): string
    {
        return config('taskmate.user_types.'.$user->user_type->value, $user->user_type->value);
    }

    private function notice(): string
    {
        return 'TaskMate records completion and payment status only. Payments are made directly between the poster and the student. TaskMate does not hold, process, recover, or guarantee payment.';
    }
}
