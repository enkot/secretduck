<?php

namespace App\Actions\Guests;

use App\Enums\InvitationEventType;
use App\Models\GuestSession;
use App\Models\Invitation;
use App\Models\InvitationEvent;
use App\Models\InvitationRecipient;

final class RecordInvitationEvent
{
    /** @param array<string, scalar|null> $metadata */
    public function handle(
        Invitation $invitation,
        InvitationEventType $type,
        ?InvitationRecipient $recipient = null,
        ?GuestSession $guestSession = null,
        ?string $idempotencyKey = null,
        array $metadata = [],
    ): InvitationEvent {
        $attributes = [
            'invitation_id' => $invitation->id,
            'recipient_id' => $recipient?->id,
            'guest_session_id' => $guestSession?->id,
            'type' => $type,
            'metadata' => $metadata === [] ? null : $metadata,
            'occurred_at' => now(),
        ];

        if ($idempotencyKey !== null) {
            return InvitationEvent::query()->firstOrCreate(['idempotency_key' => $idempotencyKey], $attributes);
        }

        return InvitationEvent::query()->create($attributes);
    }
}
