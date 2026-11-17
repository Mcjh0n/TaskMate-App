<?php

namespace App\Policies;

use App\Enums\ApplicationStatus;
use App\Enums\AgreementStatus;
use App\Enums\TaskStatus;
use App\Enums\PaymentEventType;
use App\Models\Agreement;
use App\Models\Task;
use App\Models\User;
use App\Services\RatingVisibility;

class AgreementPolicy
{
    public function create(User $user, Task $task): bool
    {
        return $task->poster_id === $user->id
            && $task->status === TaskStatus::Assigned
            && $task->applications()->where('status', ApplicationStatus::Accepted->value)->count() === 1
            && ! $task->hasActiveAgreement();
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

    public function markDelivered(User $user, Agreement $agreement): bool
    {
        return $agreement->student_id === $user->id && $agreement->status === AgreementStatus::Agreed;
    }

    public function recordCompletion(User $user, Agreement $agreement): bool
    {
        return $agreement->poster_id === $user->id && $agreement->status === AgreementStatus::Agreed;
    }

    public function markPaid(User $user, Agreement $agreement): bool
    {
        return $agreement->poster_id === $user->id && $agreement->status === AgreementStatus::Agreed;
    }

    public function respondPayment(User $user, Agreement $agreement): bool
    {
        return $agreement->student_id === $user->id && $agreement->status === AgreementStatus::Agreed;
    }

    public function reportNonPayment(User $user, Agreement $agreement): bool
    {
        return $agreement->student_id === $user->id && $agreement->status === AgreementStatus::Agreed;
    }

    public function rate(User $user, Agreement $agreement): bool
    {
        return ! $user->isAdmin()
            && $agreement->status === AgreementStatus::Agreed
            && $agreement->task->status === TaskStatus::Completed
            && $this->isParty($user, $agreement)
            && ! $agreement->ratings()->where('rater_id', $user->id)->exists()
            && ! app(RatingVisibility::class)->windowClosed($agreement);
    }

    private function isParty(User $user, Agreement $agreement): bool
    {
        return in_array($user->id, [$agreement->poster_id, $agreement->student_id], true);
    }
}
