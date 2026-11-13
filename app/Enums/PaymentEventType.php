<?php

namespace App\Enums;

enum PaymentEventType: string
{
    case MarkedPaid = 'marked_paid';
    case ConfirmedReceived = 'confirmed_received';
    case ReportedNotReceived = 'reported_not_received';
}
