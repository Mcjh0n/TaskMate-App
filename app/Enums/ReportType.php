<?php

namespace App\Enums;

enum ReportType: string
{
    case NonPayment = 'non_payment';
    case MisleadingDescription = 'misleading_description';
    case Harassment = 'harassment';
    case UnsafeBehavior = 'unsafe_behavior';
    case AcademicDishonesty = 'academic_dishonesty';
    case FraudulentPost = 'fraudulent_post';
    case RuleBypassRequest = 'rule_bypass_request';
    case OffPlatformTransaction = 'off_platform_transaction';
    case IncompleteWork = 'incomplete_work';
    case Other = 'other';

    public function label(): string { return config('taskmate.reports.types.'.$this->value.'.label', str($this->value)->replace('_', ' ')->title()->toString()); }
}
