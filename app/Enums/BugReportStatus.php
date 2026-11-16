<?php

namespace App\Enums;

enum BugReportStatus: string
{
    case Open = 'open';
    case Fixed = 'fixed';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
