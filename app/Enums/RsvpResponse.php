<?php

namespace App\Enums;

enum RsvpResponse: string
{
    case Attending = 'attending';
    case NotAttending = 'not_attending';
    case Maybe = 'maybe';

    public function label(): string
    {
        return match ($this) {
            self::Attending => 'Attending',
            self::NotAttending => 'Not attending',
            self::Maybe => 'Maybe',
        };
    }
}
