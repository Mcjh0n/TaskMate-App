<?php

namespace App\Enums;

enum TaskStatus: string
{
    case PendingReview = 'pending_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'Pending review',
            self::ChangesRequested => 'Changes requested',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function posterMayEdit(): bool
    {
        return in_array($this, [self::PendingReview, self::ChangesRequested, self::Approved], true);
    }
}
