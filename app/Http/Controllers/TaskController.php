<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

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

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $task = $request->user()->tasks()->make($request->safe()->except('guidelines_confirmed'));
        $task->status = TaskStatus::PendingReview;
        $task->guidelines_confirmed_at = now();
        $task->save();

        return redirect()->route('tasks.show', $task)->with('status', 'Your task was submitted for review.');
    }

    public function mine(Request $request): Response
    {
        $tasks = $request->user()->tasks()
            ->latest()
            ->paginate(12)
            ->through(fn (Task $task) => $this->summary($task));

        return Inertia::render('tasks/mine', [
            'tasks' => $tasks,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function show(Request $request, Task $task): Response
    {
        Gate::authorize('view', $task);

        return Inertia::render('tasks/show', [
            'task' => $this->details($task),
            'statusLabel' => $task->status->label(),
            'canEdit' => $request->user()->can('update', $task),
            'canCancel' => $request->user()->can('cancel', $task),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function edit(Request $request, Task $task): Response
    {
        Gate::authorize('update', $task);

        return Inertia::render('tasks/edit', [
            'options' => $this->options(),
            'task' => $this->details($task),
            'resetWarning' => in_array($task->status, [TaskStatus::Approved, TaskStatus::ChangesRequested], true),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $wasReviewed = in_array($task->status, [TaskStatus::Approved, TaskStatus::ChangesRequested], true);
        $task->fill($request->safe()->except('guidelines_confirmed'));
        $task->guidelines_confirmed_at = now();

        if ($wasReviewed) {
            $task->status = TaskStatus::PendingReview;
            $task->review_note = null;
            $task->reviewed_by = null;
            $task->reviewed_at = null;
        }

        $task->save();

        return redirect()->route('tasks.show', $task)->with('status', 'Your task was updated and submitted for review.');
    }

    public function cancel(Request $request, Task $task): RedirectResponse
    {
        Gate::authorize('cancel', $task);
        $task->status = TaskStatus::Cancelled;
        $task->save();

        return redirect()->route('tasks.mine')->with('status', 'Your task was cancelled.');
    }

    private function options(): array
    {
        return [
            'categories' => $this->optionsFor(config('taskmate.categories', [])),
            'workModes' => $this->optionsFor(config('taskmate.work_modes', [])),
            'complexities' => $this->optionsFor(config('taskmate.complexity_levels', [])),
            'guidelines' => config('taskmate.task_guidelines', []),
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
            'reviewNote' => in_array($task->status, [TaskStatus::ChangesRequested, TaskStatus::Rejected], true) ? $task->review_note : null,
            'createdAt' => $task->created_at?->toDateTimeString(),
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
        ];
    }
}
