<?php

namespace App\Policies;

use App\Enums\ApplicationStatus;
use App\Enums\AgreementStatus;
use App\Enums\TaskStatus;
use App\Models\Agreement;
use App\Models\Task;
use App\Models\User;

class AgreementPolicy
{
    public function create(User $user, Task $task): bool
    {
        return $task->poster_id === $user->id
            && $task->status === TaskStatus::Assigned
            && $task->applications()->where('status', ApplicationStatus::Accepted->value)->count() === 1
            && ! $task->agreement()->exists();
    }

    public function view(User $user, Agreement $agreement): bool
    {
        return in_array($user->id, [$agreement->poster_id, $agreement->student_id], true);
    }

    public function revise(User $user, Agreement $agreement): bool
    {
        return $this->isParty($user, $agreement) && $agreement->status === AgreementStatus::Negotiating;
    }

    public function confirm(User $user, Agreement $agreement): bool
    {
        return $this->isParty($user, $agreement) && $agreement->status === AgreementStatus::Negotiating;
    }

    public function sendBack(User $user, Agreement $agreement): bool
    {
        return $this->isParty($user, $agreement) && $agreement->status === AgreementStatus::Negotiating;
    }

    public function cancel(User $user, Agreement $agreement): bool
    {
        return $this->isParty($user, $agreement) && $agreement->status === AgreementStatus::Negotiating;
    }

    private function isParty(User $user, Agreement $agreement): bool
    {
        return in_array($user->id, [$agreement->poster_id, $agreement->student_id], true);
    }
}
