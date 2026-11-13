<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Open = 'open';
    case UnderReview = 'under_review';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::UnderReview => 'Under review',
            self::Resolved => 'Resolved',
            self::Dismissed => 'Dismissed',
        };
    }
}
