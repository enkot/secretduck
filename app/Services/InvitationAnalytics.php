<?php

namespace App\Services;

use App\Data\HostRsvpData;
use App\Enums\RsvpResponse;
use App\Models\Invitation;
use App\Models\InvitationRecipient;

final class InvitationAnalytics
{
    public function __construct(private readonly DateTimeFormatter $dateTimeFormatter) {}

    /** @return array<string, int|float> */
    public function summary(Invitation $invitation): array
    {
        $funnel = $invitation->recipients()
            ->whereNull('revoked_at')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened')
            ->selectRaw('SUM(CASE WHEN challenge_completed_at IS NOT NULL THEN 1 ELSE 0 END) as completed')
            ->selectRaw('SUM(CASE WHEN revealed_at IS NOT NULL THEN 1 ELSE 0 END) as revealed')
            ->first();
        $responses = $invitation->recipients()
            ->whereNull('invitation_recipients.revoked_at')
            ->join('rsvps', 'rsvps.recipient_id', '=', 'invitation_recipients.id')
            ->selectRaw('rsvps.response, COUNT(*) as aggregate')
            ->groupBy('rsvps.response')
            ->pluck('aggregate', 'response');
        $total = (int) ($funnel->total ?? 0);
        $opened = (int) ($funnel->opened ?? 0);
        $completed = (int) ($funnel->completed ?? 0);
        $revealed = (int) ($funnel->revealed ?? 0);
        $responded = (int) $responses->sum();

        return [
            'total' => $total,
            'opened' => $opened,
            'completed' => $completed,
            'revealed' => $revealed,
            'attending' => (int) ($responses[RsvpResponse::Attending->value] ?? 0),
            'notAttending' => (int) ($responses[RsvpResponse::NotAttending->value] ?? 0),
            'maybe' => (int) ($responses[RsvpResponse::Maybe->value] ?? 0),
            'noResponse' => max(0, $total - $responded),
            'revoked' => $invitation->recipients()->whereNotNull('revoked_at')->count(),
            'completionRate' => $opened > 0 ? round(($completed / $opened) * 100, 1) : 0.0,
            'rsvpRate' => $revealed > 0 ? round(($responded / $revealed) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return list<array{
     *     publicId: string,
     *     name: string,
     *     email: string|null,
     *     maxGuests: int,
     *     openedAt: string|null,
     *     lastOpenedAt: string|null,
     *     challengeStartedAt: string|null,
     *     completedAt: string|null,
     *     revealedAt: string|null,
     *     revokedAt: string|null,
     *     expiresAt: string|null,
     *     rsvp: array{
     *         respondentName: string,
     *         response: string,
     *         responseLabel: string,
     *         guestCount: int,
     *         dietaryNotes: string|null,
     *         message: string|null,
     *         submittedAt: string,
     *         submittedAtLabel: string
     *     }|null
     * }>
     */
    public function recipients(Invitation $invitation): array
    {
        $recipients = $invitation->recipients()
            ->with('rsvp:id,recipient_id,respondent_name,response,guest_count,dietary_notes,message,submitted_at')
            ->latest()
            ->get(['id', 'public_id', 'name', 'email', 'max_guests', 'opened_at', 'last_opened_at', 'challenge_started_at', 'challenge_completed_at', 'revealed_at', 'revoked_at', 'expires_at'])
            ->map(fn (InvitationRecipient $recipient): array => [
                'publicId' => $recipient->public_id,
                'name' => $recipient->name,
                'email' => $recipient->email,
                'maxGuests' => $recipient->max_guests,
                'openedAt' => $recipient->opened_at?->toIso8601String(),
                'lastOpenedAt' => $recipient->last_opened_at?->toIso8601String(),
                'challengeStartedAt' => $recipient->challenge_started_at?->toIso8601String(),
                'completedAt' => $recipient->challenge_completed_at?->toIso8601String(),
                'revealedAt' => $recipient->revealed_at?->toIso8601String(),
                'revokedAt' => $recipient->revoked_at?->toIso8601String(),
                'expiresAt' => $recipient->expires_at?->toIso8601String(),
                'rsvp' => $recipient->rsvp === null ? null : HostRsvpData::from(
                    $recipient->rsvp,
                    $this->dateTimeFormatter->dateTime($recipient->rsvp->submitted_at, $invitation->timezone),
                ),
            ])
            ->all();

        return array_values($recipients);
    }
}
