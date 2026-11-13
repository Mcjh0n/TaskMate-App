<?php

namespace App\Enums;

enum ContentFlagStatus: string
{
    case Open = 'open';
    case Reviewed = 'reviewed';
    case Dismissed = 'dismissed';
}
