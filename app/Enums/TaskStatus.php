<?php

namespace App\Enums;

enum TaskStatus: string
{
    case PendingReview = 'pending_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Removed = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'Pending review',
            self::ChangesRequested => 'Changes requested',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Assigned => 'Assigned',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::Removed => 'Removed by administrator',
        };
    }

    public function posterMayEdit(): bool
    {
        return in_array($this, [self::PendingReview, self::ChangesRequested, self::Approved], true);
    }
}
