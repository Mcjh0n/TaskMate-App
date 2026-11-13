<?php

namespace App\Enums;

enum ReportOutcome: string
{
    case Supported = 'supported';
    case NotSupported = 'not_supported';
    case Inconclusive = 'inconclusive';

    public function label(): string
    {
        return match ($this) {
            self::Supported => 'Supported',
            self::NotSupported => 'Not supported',
            self::Inconclusive => 'Inconclusive',
        };
    }
}
