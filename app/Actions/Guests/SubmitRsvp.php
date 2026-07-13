<?php

namespace App\Actions\Guests;

use App\Enums\InvitationEventType;
use App\Enums\RsvpResponse;
use App\Models\GuestSession;
use App\Models\InvitationRecipient;
use App\Models\Rsvp;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class SubmitRsvp
{
    public function __construct(private RecordInvitationEvent $recordEvent) {}

    /** @param array<string, mixed> $attributes */
    public function handle(GuestSession $session, array $attributes): Rsvp
    {
        return DB::transaction(function () use ($session, $attributes): Rsvp {
            $recipient = InvitationRecipient::query()
                ->with(['invitation', 'rsvp'])
                ->lockForUpdate()
                ->findOrFail($session->recipient_id);
            abort_unless($recipient->hasActiveAccess() && $recipient->isUnlocked(), 404);

            $deadline = $recipient->invitation->rsvpClosesAt();
            if ($deadline?->isPast()) {
                throw ValidationException::withMessages(['rsvp' => 'The RSVP deadline has passed.']);
            }

            $response = RsvpResponse::from($attributes['response']);
            $guestCount = (int) $attributes['guest_count'];
            if (($response === RsvpResponse::NotAttending && $guestCount !== 0)
                || ($response !== RsvpResponse::NotAttending && ($guestCount < 1 || $guestCount > $recipient->max_guests))) {
                throw ValidationException::withMessages(['guest_count' => "Guest count must be within this invitation's limit."]);
            }

            $normalized = [
                'respondent_name' => trim((string) $attributes['respondent_name']),
                'response' => $response,
                'guest_count' => $guestCount,
                'dietary_notes' => filled($attributes['dietary_notes'] ?? null) ? trim((string) $attributes['dietary_notes']) : null,
                'message' => filled($attributes['message'] ?? null) ? trim((string) $attributes['message']) : null,
            ];
            $rsvp = $recipient->rsvp;

            if ($rsvp !== null && $this->matches($rsvp, $normalized)) {
                return $rsvp;
            }

            $created = $rsvp === null;
            $rsvp ??= new Rsvp(['recipient_id' => $recipient->id]);
            $rsvp->fill([...$normalized, 'submitted_at' => now()])->save();
            $rsvp->revisions()->create([...$normalized, 'guest_session_id' => $session->id]);
            $this->recordEvent->handle(
                $recipient->invitation,
                $created ? InvitationEventType::RsvpSubmitted : InvitationEventType::RsvpUpdated,
                $recipient,
                $session,
                $created ? "recipient:{$recipient->id}:rsvp-submitted" : null,
            );

            return $rsvp->refresh();
        });
    }

    /** @param array<string, mixed> $attributes */
    private function matches(Rsvp $rsvp, array $attributes): bool
    {
        return $rsvp->respondent_name === $attributes['respondent_name']
            && $rsvp->response === $attributes['response']
            && $rsvp->guest_count === $attributes['guest_count']
            && $rsvp->dietary_notes === $attributes['dietary_notes']
            && $rsvp->message === $attributes['message'];
    }
}
