<?php

namespace App\Policies;

use App\Enums\ApplicationStatus;
use App\Enums\TaskStatus;
use App\Models\Application;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ApplicationPolicy
{
    public function create(User $user, Task $task): Response
    {
        if (! $user->canApply()) {
            return Response::deny('Only verified students can apply to tasks.');
        }
        if ($task->status !== TaskStatus::Approved) {
            return Response::deny('This task is not approved and cannot accept applications.');
        }
        if ($task->poster_id === $user->id) {
            return Response::deny('You cannot apply to your own task.');
        }
        if ($task->applications()->where('applicant_id', $user->id)->exists()) {
            return Response::deny('You have already applied to this task and cannot apply again.');
        }

        return Response::allow();
    }

    public function withdraw(User $user, Application $application): bool
    {
        return $application->applicant_id === $user->id && $application->status === ApplicationStatus::Pending;
    }

    public function respond(User $user, Application $application): bool
    {
        return $application->task->poster_id === $user->id
            && $application->status === ApplicationStatus::Pending
            && $application->task->status === TaskStatus::Approved;
    }
}
