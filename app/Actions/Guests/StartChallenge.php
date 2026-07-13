<?php

namespace App\Actions\Guests;

use App\Enums\InvitationEventType;
use App\Models\GuestSession;
use App\Models\InvitationRecipient;
use Illuminate\Support\Facades\DB;

final readonly class StartChallenge
{
    public function __construct(private RecordInvitationEvent $recordEvent) {}

    public function handle(GuestSession $session): InvitationRecipient
    {
        return DB::transaction(function () use ($session): InvitationRecipient {
            $recipient = InvitationRecipient::query()->lockForUpdate()->findOrFail($session->recipient_id);

            if ($recipient->challenge_started_at === null) {
                $recipient->update(['challenge_started_at' => now()]);
                $this->recordEvent->handle(
                    $recipient->invitation,
                    InvitationEventType::ChallengeStarted,
                    $recipient,
                    $session,
                    "recipient:{$recipient->id}:challenge-started",
                );
            }

            return $recipient->refresh();
        });
    }
}
