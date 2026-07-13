<?php

namespace App\Data;

use App\Models\Rsvp;

final class HostRsvpData
{
    /**
     * @return array{
     *     respondentName: string,
     *     response: string,
     *     responseLabel: string,
     *     guestCount: int,
     *     dietaryNotes: string|null,
     *     message: string|null,
     *     submittedAt: string,
     *     submittedAtLabel: string
     * }
     */
    public static function from(Rsvp $rsvp, string $submittedAtLabel): array
    {
        return [
            'respondentName' => $rsvp->respondent_name,
            'response' => $rsvp->response->value,
            'responseLabel' => $rsvp->response->label(),
            'guestCount' => $rsvp->guest_count,
            'dietaryNotes' => $rsvp->dietary_notes,
            'message' => $rsvp->message,
            'submittedAt' => $rsvp->submitted_at->toIso8601String(),
            'submittedAtLabel' => $submittedAtLabel,
        ];
    }
}
