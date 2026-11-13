<?php

namespace App\Enums;

enum SanctionType: string
{
    case Warning = 'warning';
    case Suspension = 'suspension';
    case Ban = 'ban';
}
