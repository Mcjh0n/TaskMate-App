<?php

namespace App\Policies;

use App\Enums\AgreementStatus;
use App\Enums\ApplicationStatus;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canPost();
    }

    public function create(User $user): bool
    {
        return $user->canPost();
    }

    public function view(User $user, Task $task): bool
    {
        return $task->poster_id === $user->id;
    }

    public function update(User $user, Task $task): bool
    {
        return $task->poster_id === $user->id
            && $task->status->posterMayEdit()
            && ! $task->applications()->whereIn('status', [ApplicationStatus::Pending->value, ApplicationStatus::Accepted->value])->exists();
    }

    public function cancel(User $user, Task $task): bool
    {
        return $task->poster_id === $user->id
            && ! $task->agreements()->whereIn('status', [AgreementStatus::Negotiating->value, AgreementStatus::Agreed->value])->exists()
            && ! in_array($task->status, [TaskStatus::Cancelled, TaskStatus::Rejected, TaskStatus::Removed], true);
    }
}
