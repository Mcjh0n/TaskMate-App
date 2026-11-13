<?php

namespace App\Enums;

enum ReportType: string
{
    case NonPayment = 'non_payment';

    public function label(): string { return 'Non-payment'; }
}
