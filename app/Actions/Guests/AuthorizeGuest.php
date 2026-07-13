<?php

namespace App\Actions\Guests;

use App\Enums\InvitationEventType;
use App\Models\GuestSession;
use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\ValueObjects\RecipientToken;
use Illuminate\Support\Facades\DB;

final readonly class AuthorizeGuest
{
    public function __construct(private RecordInvitationEvent $recordEvent) {}

    /** @return array{session: GuestSession, browserToken: RecipientToken} */
    public function handle(Invitation $invitation, string $rawRecipientToken): array
    {
        return DB::transaction(function () use ($invitation, $rawRecipientToken): array {
            $recipient = InvitationRecipient::query()
                ->where('invitation_id', $invitation->id)
                ->where('token_hash', hash('sha256', $rawRecipientToken))
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($recipient->isExpired(), 404);

            $browserToken = RecipientToken::generate();
            $now = now();
            $session = $recipient->guestSessions()->create([
                'browser_token_hash' => $browserToken->hash(),
                'recipient_token_version' => $recipient->token_version,
                'authorized_at' => $now,
                'last_seen_at' => $now,
                'expires_at' => $now->copy()->addDays(config('questinvite.guest_session_days')),
            ]);

            $recipient->update([
                'opened_at' => $recipient->opened_at ?? $now,
                'last_opened_at' => $now,
            ]);

            $this->recordEvent->handle(
                $invitation,
                InvitationEventType::LinkOpened,
                $recipient,
                $session,
                "recipient:{$recipient->id}:opened",
            );

            $session->setRelation('recipient', $recipient->fresh(['invitation.challenge', 'rsvp']));

            return compact('session', 'browserToken');
        });
    }
}
