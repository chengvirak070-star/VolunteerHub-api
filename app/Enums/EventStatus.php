<?php

namespace App\Enums;

enum EventStatus: string
{
    case Pending = 'pending';
    case Open = 'open';
    case Full = 'full';
    case Closed = 'closed';
    case Rejected = 'rejected';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
