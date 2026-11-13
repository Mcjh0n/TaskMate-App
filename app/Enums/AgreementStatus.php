<?php

namespace App\Enums;

enum AgreementStatus: string
{
    case Negotiating = 'negotiating';
    case Agreed = 'agreed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Negotiating => 'Negotiating',
            self::Agreed => 'Agreed',
            self::Cancelled => 'Cancelled',
        };
    }
}
