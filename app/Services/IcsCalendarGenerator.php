<?php

namespace App\Services;

use App\Models\Invitation;

final class IcsCalendarGenerator
{
    public function generate(Invitation $invitation): string
    {
        $startsAt = $invitation->starts_at?->copy()->utc();
        abort_if($startsAt === null, 404);
        $description = collect([$invitation->description, $invitation->dress_code ? "Dress code: {$invitation->dress_code}" : null])
            ->filter()->join("\n\n");

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//SecretDuck//Invitation//EN',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:'.$invitation->public_id.'@secretduck',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$startsAt->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escape((string) $invitation->title),
            'LOCATION:'.$this->escape(collect([$invitation->venue_name, $invitation->address])->filter()->join(', ')),
            'DESCRIPTION:'.$this->escape($description),
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n", "\r"], ['\\\\', '\\;', '\\,', '\\n', '\\n', '\\n'], $value);
    }
}
