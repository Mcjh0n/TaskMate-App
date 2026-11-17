<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Enums\ApplicationStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Services\RiskScreening\RiskAssessment;
use App\Services\RiskScreening\TaskModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\RatingSummary;
use App\Services\OffPlatformScanner;
use App\Services\SanctionService;
use App\Services\TaskMateNotifications;

class TaskController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('tasks/create', [
            'options' => $this->options(),
            'task' => null,
            'resetWarning' => false,
        ]);
    }

    public function store(StoreTaskRequest $request, TaskModerationService $moderation, OffPlatformScanner $scanner): RedirectResponse
    {
        [$task, $assessment] = DB::transaction(function () use ($request, $moderation, $scanner): array {
            $task = $request->user()->tasks()->make($request->safe()->except('guidelines_confirmed'));
            $task->status = TaskStatus::PendingReview;
            $task->guidelines_confirmed_at = now();
            $task->save();
            foreach (['title', 'description', 'required_activities', 'deliverables', 'skill_requirements'] as $field) {
                if ($task->{$field}) $scanner->flag($task, $request->user(), $field, $task->{$field});
            }

            return [$task, $moderation->screenAndApply($task)];
        });

        return redirect()->route('tasks.show', $task)->with('status', $moderation->posterMessage($assessment));
    }

    public function mine(Request $request): Response
    {
        $canAct = ! app(SanctionService::class)->suspended($request->user());
        $tasks = $request->user()->tasks()
            ->with('agreement')
            ->latest()
            ->paginate(12)
            ->through(fn (Task $task) => [
                ...$this->summary($task),
                'applicantsCount' => $task->applications()->count(),
                'canCancel' => $canAct && $request->user()->can('cancel', $task),
                'canEdit' => $canAct && $request->user()->can('update', $task),
                'paymentStatus' => $task->agreement?->paymentStatus()->value,
                'paymentStatusLabel' => $task->agreement?->paymentStatus()->label(),
                'agreementId' => $task->agreement?->id,
                'needsRating' => $task->status === TaskStatus::Completed && $task->agreement?->ratings()->where('rater_id', $request->user()->id)->doesntExist(),
            ]);

        return Inertia::render('tasks/mine', [
            'tasks' => $tasks,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function show(Request $request, Task $task, RatingSummary $ratingSummaries): Response
    {
        Gate::authorize('view', $task);
        $canAct = ! app(SanctionService::class)->suspended($request->user());

        $applicants = in_array($task->status, [TaskStatus::Approved, TaskStatus::Assigned], true)
            ? $task->applications()->with(['applicant:id,name,user_type,verification_status,affiliation', 'conversation'])->latest()->get()->map(fn ($application) => [
                'id' => $application->id,
                'applicant' => [
                    'id' => $application->applicant->id,
                    'name' => $application->applicant->name,
                    'type' => config('taskmate.user_types.'.$application->applicant->user_type->value),
                    'affiliation' => $application->applicant->affiliation,
                    'verificationStatus' => $application->applicant->verification_status->value,
                    'profileUrl' => route('users.show', $application->applicant),
                    'ratingSummary' => $ratingSummaries->forUser($application->applicant),
                ],
                'message' => $application->message,
                'proposedAmount' => $application->proposed_amount,
                'status' => $application->status->value,
                'statusLabel' => $application->status->label(),
                'appliedAt' => $application->created_at?->toDateTimeString(),
                'conversationId' => $application->conversation?->id,
                'messageBlockedReason' => $application->conversation?->sendingBlockedReason($request->user()),
            ])->values()
            : collect();

        return Inertia::render('tasks/show', [
            'task' => $this->details($task),
            'statusLabel' => $task->status->label(),
            'canEdit' => $canAct && $request->user()->can('update', $task),
            'canCancel' => $canAct && $request->user()->can('cancel', $task),
            'canRespondApplications' => $canAct && $task->poster_id === $request->user()->id,
            'status' => $request->session()->get('status'),
            'applicants' => $applicants,
            'agreement' => $task->agreement ? [
                'id' => $task->agreement->id,
                'status' => $task->agreement->status->value,
                'statusLabel' => $task->agreement->status->label(),
                'paymentStatus' => $task->agreement->paymentStatus()->value,
                'paymentStatusLabel' => $task->agreement->paymentStatus()->label(),
                'needsRating' => $task->status === TaskStatus::Completed && $task->agreement->ratings()->where('rater_id', $request->user()->id)->doesntExist(),
            ] : null,
            'canProposeAgreement' => $canAct && $task->poster_id === $request->user()->id
                && $task->status === TaskStatus::Assigned
                && $task->applications()->where('status', ApplicationStatus::Accepted->value)->count() === 1
                && ! $task->hasActiveAgreement(),
            'problemReport' => [
                'taskAction' => $task->poster_id === $request->user()->id ? null : route('tasks.problem-reports.store', $task),
                'types' => collect(config('taskmate.reports.types', []))->except(['non_payment', 'incomplete_work'])->map(fn ($settings, $value) => ['value' => $value, 'label' => $settings['label']])->values(),
                'applicantActions' => $applicants->map(fn ($application) => ['applicationId' => $application['id'], 'action' => route('applications.problem-reports.store', [$task, $application['id']])])->values(),
                'flaggedApplicants' => $task->applications()->whereHas('contentFlags', fn ($q) => $q->where('source_user_id', '!=', $request->user()->id)->where('status', 'open'))->pluck('id')->all(),
                'offPlatformWarning' => $task->contentFlags()->where('source_user_id', '!=', $request->user()->id)->where('status', 'open')->exists(),
            ],
        ]);
    }

    public function edit(Request $request, Task $task): Response
    {
        if ($task->status === TaskStatus::Assigned || $task->applications()->whereIn('status', [ApplicationStatus::Pending->value, ApplicationStatus::Accepted->value])->exists()) {
            abort(403, 'This task has active applicants or is assigned. Cancel it and post a new task to change its details.');
        }
        Gate::authorize('update', $task);

        return Inertia::render('tasks/edit', [
            'options' => $this->options(),
            'task' => $this->details($task),
            'resetWarning' => in_array($task->status, [TaskStatus::Approved, TaskStatus::ChangesRequested], true),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task, TaskModerationService $moderation, OffPlatformScanner $scanner): RedirectResponse
    {
        if ($task->status === TaskStatus::Assigned || $task->applications()->whereIn('status', [ApplicationStatus::Pending->value, ApplicationStatus::Accepted->value])->exists()) {
            abort(403, 'This task has active applicants or is assigned. Cancel it and post a new task to change its details.');
        }
        $assessment = DB::transaction(function () use ($request, $task, $moderation, $scanner): RiskAssessment {
            $task->fill($request->safe()->except('guidelines_confirmed'));
            $task->guidelines_confirmed_at = now();
            $task->status = TaskStatus::PendingReview;
            $task->review_note = null;
            $task->reviewed_by = null;
            $task->reviewed_at = null;
            $task->save();
            foreach (['title', 'description', 'required_activities', 'deliverables', 'skill_requirements'] as $field) {
                if ($task->{$field}) $scanner->flag($task, $request->user(), $field, $task->{$field});
            }

            return $moderation->screenAndApply($task);
        });

        return redirect()->route('tasks.show', $task)->with('status', $moderation->posterMessage($assessment));
    }

    public function cancel(Request $request, Task $task, TaskMateNotifications $alerts): RedirectResponse
    {
        Gate::authorize('cancel', $task);
        DB::transaction(function () use ($task, $alerts, $request): void {
            $lockedTask = Task::query()->lockForUpdate()->findOrFail($task->id);
            $now = now();
            $pending = $lockedTask->applications()->with('applicant')->where('status', ApplicationStatus::Pending->value)->get();
            $accepted = $lockedTask->applications()->with('applicant')->where('status', ApplicationStatus::Accepted->value)->get();
            $lockedTask->applications()->where('status', ApplicationStatus::Pending->value)
                ->update(['status' => ApplicationStatus::Declined->value, 'responded_at' => $now, 'updated_at' => $now]);
            $lockedTask->applications()->where('status', ApplicationStatus::Accepted->value)
                ->update(['status' => ApplicationStatus::Cancelled->value, 'responded_at' => $now, 'updated_at' => $now]);
            $lockedTask->status = TaskStatus::Cancelled;
            $lockedTask->save();
            foreach ($pending as $application) $alerts->send($application->applicant, 'application.declined', route('applications.mine', [], false), ['task_id' => $lockedTask->id, 'application_id' => $application->id], ['task_title' => $lockedTask->title], $request->user()->id);
            foreach ($accepted as $application) $alerts->send($application->applicant, 'application.cancelled', route('applications.mine', [], false), ['task_id' => $lockedTask->id, 'application_id' => $application->id], ['task_title' => $lockedTask->title], $request->user()->id);
        });

        return redirect()->route('tasks.mine')->with('status', 'Your task was cancelled.');
    }

    private function options(): array
    {
        return [
            'categories' => $this->optionsFor(config('taskmate.categories', [])),
            'workModes' => $this->optionsFor(config('taskmate.work_modes', [])),
            'complexities' => $this->optionsFor(config('taskmate.complexity_levels', [])),
            'guidelines' => config('taskmate.task_guidelines', []),
            'riskQuestions' => collect(config('taskmate.risk_questions', []))
                ->map(fn (array $items) => $this->optionsFor($items))
                ->all(),
        ];
    }

    private function optionsFor(array $options): array
    {
        return collect($options)->map(fn (string $label, string $value) => compact('value', 'label'))->values()->all();
    }

    private function summary(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'category' => config('taskmate.categories.'.$task->category, $task->category),
            'budget' => $task->budget,
            'workMode' => config('taskmate.work_modes.'.$task->work_mode, $task->work_mode),
            'status' => $task->status->value,
            'statusLabel' => $task->status->label(),
            'hasActiveApplications' => $task->applications()->whereIn('status', [ApplicationStatus::Pending->value, ApplicationStatus::Accepted->value])->exists(),
            'reviewNote' => in_array($task->status, [TaskStatus::ChangesRequested, TaskStatus::Rejected], true) ? $task->review_note : null,
            'removalReason' => $task->status === TaskStatus::Removed ? $task->removal_reason : null,
            'createdAt' => $task->created_at?->toDateTimeString(),
            'applicantsCount' => $task->applications()->count(),
        ];
    }

    private function details(Task $task): array
    {
        return [
            ...$this->summary($task),
            'description' => $task->description,
            'requiredActivities' => $task->required_activities,
            'deliverables' => $task->deliverables,
            'estimatedDurationHours' => $task->estimated_duration_hours,
            'skillRequirements' => $task->skill_requirements,
            'complexity' => config('taskmate.complexity_levels.'.$task->complexity, $task->complexity),
            'workModeValue' => $task->work_mode,
            'workplaceName' => $task->workplace_name,
            'workplaceAddress' => $task->workplace_address,
            'deadline' => $task->deadline?->toDateString(),
            'guidelinesConfirmedAt' => $task->guidelines_confirmed_at?->toDateTimeString(),
            'physicalDemand' => $task->physical_demand,
            'academicInvolvement' => $task->academic_involvement,
            'hazardExposure' => $task->hazard_exposure,
            'hazardDescription' => $task->hazard_description,
            'privateMeetingRequired' => $task->private_meeting_required,
            'workplaceSetting' => $task->workplace_setting,
        ];
    }
}
