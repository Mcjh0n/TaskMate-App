<?php

namespace App\Http\Controllers;

use App\Enums\AgreementStatus;
use App\Enums\PaymentEventType;
use App\Enums\PaymentStatus;
use App\Enums\ReportStatus;
use App\Enums\TaskStatus;
use App\Http\Requests\CancelAgreementRequest;
use App\Http\Requests\ConfirmAgreementRequest;
use App\Http\Requests\SendAgreementRequest;
use App\Http\Requests\SendBackAgreementRequest;
use App\Http\Requests\StoreAgreementVersionRequest;
use App\Models\Agreement;
use App\Models\AgreementVersion;
use App\Models\Task;
use App\Models\User;
use App\Services\AgreementWorkflow;
use App\Services\RatingVisibility;
use App\Services\ReportWorkflow;
use App\Services\SanctionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AgreementController extends Controller
{
    public function create(Task $task, Request $request, AgreementWorkflow $workflow): RedirectResponse
    {
        $agreement = $task->agreement;
        if (! $agreement || $agreement->status === AgreementStatus::Cancelled) {
            Gate::authorize('create', [Agreement::class, $task]);
            $application = $task->applications()->where('status', 'accepted')->firstOrFail();
            $agreement = $workflow->createDraft($task, $request->user(), $application);
        }
        Gate::authorize('view', $agreement);

        return redirect()->route('agreements.show', $agreement);
    }

    public function store(SendAgreementRequest $request, Task $task, AgreementWorkflow $workflow): RedirectResponse
    {
        $agreement = $task->agreement()->firstOrFail();
        $workflow->sendDraft($agreement, $request->user());

        return redirect()->route('agreements.show', $agreement)->with('status', 'The agreement was sent to the applicant for review.');
    }

    public function show(Agreement $agreement, Request $request): Response
    {
        Gate::authorize('view', $agreement);
        $agreement->load([
            'task:id,title,budget,budget_min,budget_max,status,work_mode,workplace_name,workplace_address',
            'poster:id,name,user_type,organization_name',
            'student:id,name,user_type,affiliation',
            'currentVersion.proposer:id,name',
            'currentVersion.confirmations.user:id,name',
            'versions.proposer:id,name',
            'versions.confirmations.user:id,name',
            'messages.user:id,name',
            'task.completion',
            'completion',
            'paymentEvents.actor:id,name',
            'reports',
        ]);

        /** @var array<string, array{label: string, priority?: string, respondent_can_reply?: bool}> $reportTypes */
        $reportTypes = (array) config('taskmate.reports.types', []);
        /** @var array<string, string> $paymentMethods */
        $paymentMethods = (array) config('taskmate.payment_status.methods', []);

        return Inertia::render('agreements/show', [
            ...$this->present($agreement, $request->user()),
            'viewerId' => $request->user()->id,
            'actionsAllowed' => ! app(SanctionService::class)->suspended($request->user()),
            'termsMaxLength' => config('taskmate.agreement.terms_max_length', 3000),
            'notice' => 'This agreement records what the poster and worker agreed. It is not a legal contract. TaskMate does not process, hold, or guarantee payment. Payment is made directly between the two of you.',
            'status' => $request->session()->get('status'),
            'problemReport' => ['action' => route('agreements.problem-reports.store', $agreement), 'types' => collect($reportTypes)->except('non_payment')->map(fn ($settings, $value) => ['value' => $value, 'label' => $settings['label']])->values()],
            'offPlatformWarning' => $agreement->versions()->whereHas('contentFlags', fn ($q) => $q->where('source_user_id', '!=', $request->user()->id)->where('status', 'open'))->exists()
                || $agreement->messages()->whereHas('contentFlags', fn ($q) => $q->where('source_user_id', '!=', $request->user()->id)->where('status', 'open'))->exists(),
        ]);
    }

    public function revise(StoreAgreementVersionRequest $request, Agreement $agreement, AgreementWorkflow $workflow): RedirectResponse
    {
        $workflow->revise($agreement, $request->user(), $request->validated());

        return back()->with('status', 'A new agreement version was sent. Both parties need to confirm this version.');
    }

    public function confirm(ConfirmAgreementRequest $request, Agreement $agreement, AgreementWorkflow $workflow): RedirectResponse
    {
        $workflow->confirm($agreement, $request->user(), (int) $request->validated('version_id'));
        $agreement->refresh();

        return back()->with('status', $agreement->status === AgreementStatus::Agreed
            ? 'Both parties confirmed the agreement. The terms are now locked and work may begin.'
            : 'Your confirmation was recorded for this version.');
    }

    public function sendBack(SendBackAgreementRequest $request, Agreement $agreement, AgreementWorkflow $workflow): RedirectResponse
    {
        $data = $request->validated();
        $workflow->sendBack($agreement, $request->user(), (int) $data['version_id'], $data['body']);

        return back()->with('status', 'Your comment was sent. Submit a new version to change the proposed terms.');
    }

    public function cancel(CancelAgreementRequest $request, Agreement $agreement, AgreementWorkflow $workflow): RedirectResponse
    {
        $workflow->cancel($agreement, $request->user(), $request->validated('reason'));
        if ($request->user()->id === $agreement->poster_id) {
            return redirect()->route('tasks.show', $agreement->task_id)
                ->with('status', 'The agreement was cancelled. The task is open again so you can choose another applicant.');
        }

        return redirect()->route('applications.mine')
            ->with('status', 'The agreement was cancelled. Your application is closed, and the task is open again for other applicants.');
    }

    public function adminShow(Agreement $agreement): Response
    {
        $agreement->load([
            'task:id,title,budget,budget_min,budget_max,status,work_mode,workplace_name,workplace_address',
            'poster:id,name,user_type,organization_name', 'student:id,name,user_type,affiliation',
            'currentVersion.proposer:id,name', 'currentVersion.confirmations.user:id,name',
            'versions.proposer:id,name', 'versions.confirmations.user:id,name', 'messages.user:id,name', 'task.completion',
            'completion', 'paymentEvents.actor:id,name', 'reports',
        ]);

        return Inertia::render('admin/agreements/show', [
            ...$this->present($agreement),
            'notice' => 'This agreement records what the poster and worker agreed. It is not a legal contract. TaskMate does not process, hold, or guarantee payment. Payment is made directly between the two of you.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Agreement $agreement, ?User $viewer = null): array
    {
        $version = fn (AgreementVersion $item): array => [
            'id' => $item->id,
            'versionNumber' => $item->version_number,
            'proposerId' => $item->proposed_by,
            'proposerName' => $item->proposer->name,
            'deliverables' => $item->deliverables,
            'terms' => $item->terms,
            'finalAmount' => $item->final_amount,
            'startDate' => $item->start_date?->toDateString(),
            'durationDays' => $item->duration_days,
            'workMode' => config('taskmate.work_modes.'.$item->work_mode, config('taskmate.legacy_work_modes.'.$item->work_mode, $item->work_mode)),
            'workplaceName' => $item->workplace_name,
            'workplaceAddress' => $item->workplace_address,
            'paymentArrangement' => $item->payment_arrangement,
            'comment' => $item->comment,
            'createdAt' => $item->created_at?->toDateTimeString(),
            'confirmations' => $item->confirmations->map(fn ($confirmation) => [
                'userId' => $confirmation->user_id,
                'userName' => $confirmation->user->name,
                'confirmedAt' => $confirmation->confirmed_at?->toDateTimeString(),
            ])->values(),
        ];
        $task = $agreement->task;

        return [
            'agreement' => [
                'id' => $agreement->id,
                'status' => $agreement->status->value,
                'statusLabel' => $agreement->status->label(),
                'lockedAt' => $agreement->locked_at?->toDateTimeString(),
                'cancelledAt' => $agreement->cancelled_at?->toDateTimeString(),
                'cancelledBy' => $agreement->cancelled_by,
                'cancellationReason' => $agreement->cancellation_reason,
                'recordReference' => $agreement->status === AgreementStatus::Agreed ? $agreement->currentVersion?->content_hash : null,
                'task' => ['id' => $task->id, 'title' => $task->title, 'budget' => $task->budget, 'budgetDisplay' => $task->budgetDisplay(), 'status' => $task->status->value],
                'poster' => ['id' => $agreement->poster_id, 'name' => $agreement->poster->isOrganization() ? $agreement->poster->organization_name : $agreement->poster->name],
                'student' => ['id' => $agreement->student_id, 'name' => $agreement->student->name],
                'currentVersion' => $agreement->currentVersion ? $version($agreement->currentVersion) : null,
                'versions' => $agreement->versions->map($version)->values(),
                'messages' => $agreement->messages->map(fn ($message) => ['id' => $message->id, 'userId' => $message->user_id, 'userName' => $message->user->name, 'body' => $message->body, 'createdAt' => $message->created_at?->toDateTimeString()])->values(),
            ],
            'completionPayment' => $this->completionPaymentData($agreement, $viewer),
            'ratings' => $viewer ? $this->ratingData($agreement, $viewer) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ratingData(Agreement $agreement, User $viewer): array
    {
        $visibility = app(RatingVisibility::class);
        $own = $agreement->ratings()->where('rater_id', $viewer->id)->with('scores')->first();
        $otherId = $viewer->id === $agreement->poster_id ? $agreement->student_id : $agreement->poster_id;
        $other = $agreement->ratings()->where('rater_id', $otherId)->with('scores')->first();
        $otherVisible = $other && $visibility->isVisibleToOthers($other) ? $other : null;
        $until = $agreement->task->completion?->completed_at?->copy()->addDays((int) config('taskmate.ratings.window_days', 30));

        return [
            'canRate' => auth()->user()->can('rate', $agreement),
            'rateUrl' => route('agreements.rate', $agreement),
            'windowDate' => $until?->toDateTimeString(),
            'own' => $own ? [
                'overall' => $own->overall_experience,
                'comment' => $own->comment,
                'scores' => $own->scores->map(fn ($score) => ['dimension' => $score->dimension, 'label' => config('taskmate.ratings.dimensions.'.$own->direction->value.'.'.$score->dimension, $score->dimension), 'score' => $score->score])->values(),
            ] : null,
            'other' => $otherVisible ? [
                'overall' => $otherVisible->overall_experience,
                'comment' => $otherVisible->comment,
                'raterName' => $otherVisible->rater->isOrganization() ? $otherVisible->rater->organization_name : $otherVisible->rater->name,
                'scores' => $otherVisible->scores->map(fn ($score) => ['dimension' => $score->dimension, 'label' => config('taskmate.ratings.dimensions.'.$otherVisible->direction->value.'.'.$score->dimension, $score->dimension), 'score' => $score->score])->values(),
            ] : null,
            'waiting' => $otherVisible === null && $agreement->task->status === TaskStatus::Completed,
            'visibility' => config('taskmate.ratings.visibility', 'after_both'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function completionPaymentData(Agreement $agreement, ?User $viewer = null): array
    {
        /** @var array<string, string> $paymentMethods */
        $paymentMethods = (array) config('taskmate.payment_status.methods', []);
        $completion = $agreement->completion;
        $events = $agreement->paymentEvents;
        $latest = $events->last();
        $agreedAmount = (float) ($agreement->currentVersion->final_amount ?? 0);
        $paymentStatus = match ($latest?->event) {
            PaymentEventType::MarkedPaid => PaymentStatus::MarkedPaid,
            PaymentEventType::ConfirmedReceived => PaymentStatus::ConfirmedReceived,
            PaymentEventType::ReportedNotReceived => PaymentStatus::NotReceived,
            default => PaymentStatus::Unpaid,
        };
        $settled = $latest?->event === PaymentEventType::ConfirmedReceived
            && $latest->amount !== null
            && (float) $latest->amount === $agreedAmount;
        $userId = $viewer?->id;
        $isPoster = $userId !== null && $userId === $agreement->poster_id;
        $isWorker = $userId !== null && $userId === $agreement->student_id;
        $activeReport = $agreement->reports->first(fn ($report) => in_array($report->status, [ReportStatus::Open, ReportStatus::UnderReview], true));

        return [
            'paymentStatus' => $paymentStatus->value,
            'paymentStatusLabel' => $paymentStatus->label(),
            'agreedAmount' => $agreement->currentVersion?->final_amount,
            'settled' => $settled,
            'completion' => $completion ? [
                'deliveredAt' => $completion->delivered_at?->toDateTimeString(),
                'deliveredNote' => $completion->delivered_note,
                'completedAt' => $completion->completed_at?->toDateTimeString(),
                'completionNote' => $completion->completion_note,
            ] : null,
            'events' => $events->map(fn ($event) => [
                'id' => $event->id,
                'event' => $event->event->value,
                'eventLabel' => match ($event->event) {
                    PaymentEventType::MarkedPaid => 'Poster marked payment as paid',
                    PaymentEventType::ConfirmedReceived => 'Worker confirmed receipt',
                    PaymentEventType::ReportedNotReceived => 'Worker reported not receiving payment',
                },
                'actorName' => $event->actor->name,
                'amount' => $event->amount,
                'paidOn' => $event->paid_on?->toDateString(),
                'method' => $event->method,
                'note' => $event->note,
                'amountMismatch' => $event->amount !== null && (float) $event->amount !== $agreedAmount,
                'createdAt' => $event->created_at?->toDateTimeString(),
            ])->values(),
            'activeReportId' => $activeReport?->id,
            'canMarkDelivered' => $isWorker && $agreement->status === AgreementStatus::Agreed && $agreement->task->status === TaskStatus::InProgress && ! $completion?->delivered_at,
            'canRecordCompletion' => $isPoster && $agreement->status === AgreementStatus::Agreed && $agreement->task->status === TaskStatus::InProgress,
            'canMarkPaid' => $isPoster && $agreement->status === AgreementStatus::Agreed && in_array($agreement->task->status, [TaskStatus::InProgress, TaskStatus::Completed], true) && ! $settled,
            'canRespondPayment' => $isWorker && $agreement->status === AgreementStatus::Agreed && $latest?->event === PaymentEventType::MarkedPaid && ! $settled,
            'canReportNonPayment' => $isWorker && $agreement->status === AgreementStatus::Agreed && ! $activeReport && app(ReportWorkflow::class)->canReportNonPayment($agreement),
            'paymentMethods' => collect($paymentMethods)->map(fn (string $label, string $value) => compact('value', 'label'))->values(),
        ];
    }
}
