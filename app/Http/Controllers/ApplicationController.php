<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreApplicationRequest;
use App\Models\Application;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\ValidationException;

class ApplicationController extends Controller
{
    public function store(StoreApplicationRequest $request, Task $task): RedirectResponse
    {
        Gate::authorize('create', [Application::class, $task]);

        $application = new Application;
        $application->task_id = $task->id;
        $application->applicant_id = $request->user()->id;
        $application->message = $request->validated('message');
        $application->proposed_amount = $request->validated('proposed_amount');
        $application->status = ApplicationStatus::Pending;
        $application->save();

        return back()->with('status', 'Your application was submitted.');
    }

    public function mine(Request $request): Response
    {
        $applications = $request->user()->applications()
            ->with(['task.poster:id,name,user_type,organization_name'])
            ->latest()
            ->paginate(12)
            ->through(fn (Application $application) => [
                'id' => $application->id,
                'taskId' => $application->task->id,
                'taskTitle' => $application->task->title,
                'posterName' => $application->task->poster->isOrganization()
                    ? $application->task->poster->organization_name
                    : $application->task->poster->name,
                'budget' => $application->task->budget,
                'status' => $application->status->value,
                'statusLabel' => $application->status->label(),
                'taskStatus' => $application->task->status->label(),
                'taskApproved' => $application->task->status === TaskStatus::Approved,
                'appliedAt' => $application->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('applications/index', [
            'applications' => $applications,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function withdraw(Request $request, Application $application): RedirectResponse
    {
        Gate::authorize('withdraw', $application);
        $application->status = ApplicationStatus::Withdrawn;
        $application->responded_at = now();
        $application->save();

        return back()->with('status', 'Your application was withdrawn.');
    }

    public function accept(Request $request, Task $task, Application $application): RedirectResponse
    {
        abort_unless($application->task_id === $task->id, 404);
        DB::transaction(function () use ($request, $task, $application): void {
            $task = Task::query()->lockForUpdate()->findOrFail($task->id);
            $application->refresh();
            Gate::authorize('respond', $application);
            if ($task->status !== TaskStatus::Approved
                || $task->applications()->where('status', ApplicationStatus::Accepted->value)->exists()) {
                throw ValidationException::withMessages(['application' => 'This task already has an accepted application or is no longer available.']);
            }

            $application->status = ApplicationStatus::Accepted;
            $application->responded_at = now();
            $application->save();
            $task->status = TaskStatus::Assigned;
            $task->save();

            $task->applications()
                ->whereKeyNot($application->id)
                ->where('status', ApplicationStatus::Pending->value)
                ->update(['status' => ApplicationStatus::Declined->value, 'responded_at' => now(), 'updated_at' => now()]);
        });

        return back()->with('status', 'The applicant was accepted.');
    }

    public function decline(Request $request, Task $task, Application $application): RedirectResponse
    {
        abort_unless($application->task_id === $task->id, 404);
        DB::transaction(function () use ($request, $task, $application): void {
            $task = Task::query()->lockForUpdate()->findOrFail($task->id);
            $application->refresh();
            Gate::authorize('respond', $application);
            $application->status = ApplicationStatus::Declined;
            $application->responded_at = now();
            $application->save();
        });

        return back()->with('status', 'The application was declined.');
    }
}
