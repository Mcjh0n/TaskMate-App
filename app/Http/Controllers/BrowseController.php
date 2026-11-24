<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\BrowseTasksRequest;
use App\Models\Application;
use App\Models\Task;
use App\Services\RatingSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BrowseController extends Controller
{
    public function index(BrowseTasksRequest $request): Response
    {
        $filters = $request->validated();
        $tasks = Task::query()
            ->with('poster:id,name,user_type,verification_status,affiliation,organization_name,created_at')
            ->where('status', TaskStatus::Approved->value)
            ->whereDoesntHave('poster.sanctions', fn ($sanctions) => $sanctions->whereNull('lifted_at')->where('type', 'suspension')->where('starts_at', '<=', now())->where('ends_at', '>', now()))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(fn ($query) => $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('required_activities', 'like', "%{$search}%"));
            })
            ->when($filters['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
            ->when($filters['work_mode'] ?? null, fn ($query, string $mode) => $query->where('work_mode', $mode))
            ->when($filters['complexity'] ?? null, fn ($query, string $complexity) => $query->where('complexity', $complexity))
            ->when(isset($filters['min_budget']), fn ($query) => $query->where(fn ($query) => $query
                ->where(fn ($query) => $query->whereNotNull('budget')->where('budget', '>=', $filters['min_budget']))
                ->orWhere(fn ($query) => $query->whereNull('budget')->where('budget_max', '>=', $filters['min_budget']))))
            ->when(isset($filters['max_budget']), fn ($query) => $query->where(fn ($query) => $query
                ->where(fn ($query) => $query->whereNotNull('budget')->where('budget', '<=', $filters['max_budget']))
                ->orWhere(fn ($query) => $query->whereNull('budget')->where('budget_min', '<=', $filters['max_budget']))))
            ->when(($filters['sort'] ?? 'newest') === 'budget_high', fn ($query) => $query->orderByRaw('COALESCE(budget, budget_max) DESC'))
            ->when(($filters['sort'] ?? 'newest') === 'budget_low', fn ($query) => $query->orderByRaw('COALESCE(budget, budget_min) ASC'))
            ->when(($filters['sort'] ?? 'newest') === 'newest', fn ($query) => $query->latest()->orderByDesc('id'))
            ->paginate((int) config('taskmate.browse.per_page', 12))
            ->withQueryString()
            ->through(fn (Task $task) => $this->listing($task, $request->user()->id, $request->user()->canApply()));

        return Inertia::render('browse/index', [
            'tasks' => $tasks,
            'filters' => $filters,
            'options' => [
                'categories' => $this->options(config('taskmate.categories', [])),
                'workModes' => $this->options(config('taskmate.work_modes', [])),
                'sorts' => $this->options(config('taskmate.browse.sort_options', [])),
            ],
        ]);
    }

    public function show(Request $request, Task $task, RatingSummary $ratingSummaries): Response
    {
        abort_unless($task->status === TaskStatus::Approved, 404);
        abort_if($task->poster->sanctions()->whereNull('lifted_at')->where('type', 'suspension')->where('starts_at', '<=', now())->where('ends_at', '>', now())->exists(), 404);
        $task->load('poster:id,name,user_type,verification_status,affiliation,organization_name,created_at');
        $application = $task->applications()->where('applicant_id', $request->user()->id)->first();
        $conversation = $application ? $application->conversation()->first() : null;

        return Inertia::render('browse/show', [
            'task' => $this->details($task),
            'poster' => [...$this->poster($task), 'profileUrl' => route('users.show', $task->poster), 'ratingSummary' => $ratingSummaries->forUser($task->poster)],
            'application' => $application ? [
                'status' => $application->status->value,
                'statusLabel' => $application->status->label(),
                'appliedAt' => $application->created_at?->toDateTimeString(),
            ] : null,
            'conversation' => $conversation ? ['id' => $conversation->id, 'sendBlockedReason' => $conversation->sendingBlockedReason($request->user())] : null,
            'canApply' => $application === null && $task->status === TaskStatus::Approved
                && $request->user()->canApply()
                && $task->poster_id !== $request->user()->id,
            'applicantVerificationStatus' => $request->user()->verification_status->value,
            'problemReport' => ['action' => route('tasks.problem-reports.store', $task), 'types' => $this->problemReportTypes()],
            'offPlatformWarning' => $task->contentFlags()->where('source_user_id', '!=', $request->user()->id)->where('status', 'open')->exists(),
            'status' => $request->session()->get('status'),
            'messageMaxLength' => (int) config('taskmate.applications.message_max_length', 1000),
        ]);
    }

    public function applicationTask(Request $request, Application $application, RatingSummary $ratingSummaries): Response
    {
        abort_unless($application->applicant_id === $request->user()->id, 404);
        $task = $application->task()->with('poster:id,name,user_type,verification_status,affiliation,organization_name,created_at')->firstOrFail();
        $conversation = $application->conversation()->first();
        abort_if($task->status === TaskStatus::Removed, 404);

        return Inertia::render('browse/show', [
            'task' => $this->details($task),
            'poster' => [...$this->poster($task), 'profileUrl' => route('users.show', $task->poster), 'ratingSummary' => $ratingSummaries->forUser($task->poster)],
            'application' => [
                'status' => $application->status->value,
                'statusLabel' => $application->status->label(),
                'appliedAt' => $application->created_at?->toDateTimeString(),
            ],
            'conversation' => $conversation ? ['id' => $conversation->id, 'sendBlockedReason' => $conversation->sendingBlockedReason($request->user())] : null,
            'limitedView' => $task->status !== TaskStatus::Approved,
            'currentTaskStatus' => $task->status->label(),
            'messageMaxLength' => (int) config('taskmate.applications.message_max_length', 1000),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function listing(Task $task, int $studentId, bool $canApply): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'descriptionPreview' => Str::limit(strip_tags($task->description), 180),
            'category' => config('taskmate.categories.'.$task->category, $task->category),
            'budget' => $task->budget,
            'budgetMin' => $task->budget_min,
            'budgetMax' => $task->budget_max,
            'budgetDisplay' => $task->budgetDisplay(),
            'workMode' => config('taskmate.work_modes.'.$task->work_mode, config('taskmate.legacy_work_modes.'.$task->work_mode, $task->work_mode)),
            'estimatedDurationDays' => $task->estimated_duration_days,
            'estimatedDurationLabel' => $task->durationLabel(),
            'deliverablesPreview' => $task->deliverables,
            'workplaceName' => $task->work_mode === 'online' ? null : $task->workplace_name,
            'workplaceAddress' => $task->work_mode === 'online' ? null : $task->workplace_address,
            'posterName' => $task->poster->isOrganization() ? $task->poster->organization_name : $task->poster->name,
            'posterType' => config('taskmate.user_types.'.$task->poster->user_type->value),
            'posterVerificationStatus' => $task->poster->verification_status->value,
            'hasApplied' => $task->applications()->where('applicant_id', $studentId)->exists(),
            'canApply' => $canApply,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function details(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'category' => config('taskmate.categories.'.$task->category, $task->category),
            'description' => $task->description,
            'requiredActivities' => $task->required_activities,
            'deliverables' => $task->deliverables,
            'estimatedDurationDays' => $task->estimated_duration_days,
            'estimatedDurationLabel' => $task->durationLabel(),
            'skillRequirements' => $task->skill_requirements,
            'budget' => $task->budget,
            'budgetMin' => $task->budget_min,
            'budgetMax' => $task->budget_max,
            'budgetDisplay' => $task->budgetDisplay(),
            'workMode' => config('taskmate.work_modes.'.$task->work_mode, config('taskmate.legacy_work_modes.'.$task->work_mode, $task->work_mode)),
            'workplaceName' => $task->work_mode === 'online' ? null : $task->workplace_name,
            'workplaceAddress' => $task->work_mode === 'online' ? null : $task->workplace_address,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function poster(Task $task): array
    {
        $poster = $task->poster;

        return [
            'displayName' => $poster->isOrganization() ? $poster->organization_name : $poster->name,
            'accountType' => config('taskmate.user_types.'.$poster->user_type->value),
            'affiliation' => $poster->isOrganization() ? $poster->organization_name : $poster->affiliation,
            'verificationStatus' => $poster->verification_status->value,
            'memberSince' => $poster->created_at?->toDateString(),
        ];
    }

    /**
     * @param  array<string, string>  $values
     * @return array<int, array{value: string, label: string}>
     */
    private function options(array $values): array
    {
        return collect($values)->map(fn (string $label, string $value) => compact('value', 'label'))->values()->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function problemReportTypes(): array
    {
        /** @var array<string, array{label: string}> $reportTypes */
        $reportTypes = (array) config('taskmate.reports.types', []);

        return collect($reportTypes)->except(['non_payment', 'incomplete_work'])->map(fn ($settings, $value) => ['value' => $value, 'label' => $settings['label']])->values()->all();
    }
}
