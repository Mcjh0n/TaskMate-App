<?php

namespace App\Http\Controllers;

use App\Enums\ReportType;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreGeneralReportRequest;
use App\Models\Agreement;
use App\Models\Application;
use App\Models\Task;
use App\Models\User;
use App\Services\ReportWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class GeneralReportController extends Controller
{
    public function task(StoreGeneralReportRequest $request, Task $task, ReportWorkflow $workflow): RedirectResponse
    {
        $reporter = $request->user();
        abort_unless($task->status === TaskStatus::Approved || $task->poster_id === $reporter->id || $task->applications()->where('applicant_id', $reporter->id)->exists(), 403);
        return $this->create($request, $workflow, $task->poster, $task, null);
    }

    public function agreement(StoreGeneralReportRequest $request, Agreement $agreement, ReportWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('view', $agreement);
        $other = $request->user()->id === $agreement->poster_id ? $agreement->student : $agreement->poster;
        return $this->create($request, $workflow, $other, $agreement->task, $agreement);
    }

    public function applicant(StoreGeneralReportRequest $request, Task $task, Application $application, ReportWorkflow $workflow): RedirectResponse
    {
        abort_unless($application->task_id === $task->id && $task->poster_id === $request->user()->id, 403);
        return $this->create($request, $workflow, $application->applicant, $task, null);
    }

    public function profile(StoreGeneralReportRequest $request, User $user, ReportWorkflow $workflow): RedirectResponse
    {
        $reporter = $request->user();
        abort_if($user->id === $reporter->id || $user->isAdmin(), 403);
        $agreement = Agreement::query()->where(fn ($q) => $q
            ->where(fn ($party) => $party->where('poster_id', $reporter->id)->where('student_id', $user->id))
            ->orWhere(fn ($party) => $party->where('poster_id', $user->id)->where('student_id', $reporter->id)))->first();
        $task = Task::query()->where(fn ($q) => $q
            ->where(fn ($posted) => $posted->where('poster_id', $user->id)->whereHas('applications', fn ($a) => $a->where('applicant_id', $reporter->id)))
            ->orWhere(fn ($applied) => $applied->where('poster_id', $reporter->id)->whereHas('applications', fn ($a) => $a->where('applicant_id', $user->id))))
            ->first();
        if (! $agreement && ! $task) throw ValidationException::withMessages(['report' => 'You can report this person only when you have a related TaskMate task or agreement.']);
        return $this->create($request, $workflow, $user, $task ?? $agreement->task, $agreement);
    }

    private function create(StoreGeneralReportRequest $request, ReportWorkflow $workflow, User $target, Task $task, ?Agreement $agreement): RedirectResponse
    {
        $type = ReportType::from($request->validated('type'));
        if ($type === ReportType::IncompleteWork && ! $agreement) {
            throw ValidationException::withMessages(['type' => 'Incomplete work reports require an agreement.']);
        }
        $report = $workflow->createProblem($request->user(), $target, $task, $agreement, $type, $request->validated('description'), $request->validated('evidence', []));
        return redirect()->route('reports.show', $report)->with('status', 'Your report was submitted for review.');
    }
}
