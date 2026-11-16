<?php

namespace App\Enums;

enum ContentStatus: string
{
    case Visible = 'visible';
    case Hidden = 'hidden';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
