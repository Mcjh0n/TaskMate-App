<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case MarkedPaid = 'marked_paid';
    case ConfirmedReceived = 'confirmed_received';
    case NotReceived = 'not_received';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::MarkedPaid => 'Marked as paid',
            self::ConfirmedReceived => 'Confirmed received',
            self::NotReceived => 'Not received',
        };
    }
}
