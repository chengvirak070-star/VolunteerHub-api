<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Organizer = 'organizer';
    case Volunteer = 'volunteer';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
