<?php

namespace App\Services;

use App\Models\GuestSession;
use App\Models\Invitation;

final class RevealPayloadBuilder
{
    public function __construct(private readonly DateTimeFormatter $dateTimeFormatter) {}

    /** @return array<string, mixed> */
    public function build(Invitation $invitation, GuestSession $session): array
    {
        $recipient = $session->recipient;
        abort_unless($recipient->hasActiveAccess() && $recipient->isUnlocked(), 404);
        $rsvp = $recipient->rsvp;
        $deadline = $invitation->rsvpClosesAt();

        return [
            'heading' => $invitation->reveal_heading ?: 'You are invited',
            'title' => $invitation->title,
            'hostNames' => $invitation->host_names,
            'startsAt' => $invitation->starts_at?->toIso8601String(),
            'startsAtLabel' => $invitation->starts_at === null
                ? null
                : $this->dateTimeFormatter->dateTime($invitation->starts_at, $invitation->timezone),
            'timezone' => $invitation->timezone,
            'venueName' => $invitation->venue_name,
            'address' => $invitation->address,
            'description' => $invitation->description,
            'dressCode' => $invitation->dress_code,
            'rsvp' => [
                'currentResponse' => $rsvp === null ? null : [
                    'respondentName' => $rsvp->respondent_name,
                    'response' => $rsvp->response->value,
                    'guestCount' => $rsvp->guest_count,
                    'dietaryNotes' => $rsvp->dietary_notes,
                    'message' => $rsvp->message,
                ],
                'maxGuests' => $recipient->max_guests,
                'deadline' => $deadline?->toIso8601String(),
                'deadlineLabel' => $deadline === null
                    ? null
                    : $this->dateTimeFormatter->date($deadline, $invitation->timezone),
                'canUpdate' => $deadline === null || $deadline->isFuture(),
            ],
            'actions' => [
                'calendarUrl' => route('guest.calendar', $invitation),
                'mapUrl' => $invitation->map_url ? route('guest.map', $invitation) : null,
                'websiteUrl' => $invitation->external_url ? route('guest.website', $invitation) : null,
            ],
        ];
    }
}
