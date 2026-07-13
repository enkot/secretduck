<?php

namespace App\Actions\Invitations;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use Illuminate\Support\Facades\DB;

final class ChangeInvitationStatus
{
    public function pause(Invitation $invitation): Invitation
    {
        return $this->change($invitation, InvitationStatus::Paused, ['paused_at' => now()]);
    }

    public function archive(Invitation $invitation): Invitation
    {
        return DB::transaction(function () use ($invitation): Invitation {
            $changed = $this->change($invitation, InvitationStatus::Archived, ['archived_at' => now()]);
            $changed->recipients()->each(fn ($recipient) => $recipient->guestSessions()->update(['revoked_at' => now()]));

            return $changed;
        });
    }

    /** @param array<string, mixed> $attributes */
    private function change(Invitation $invitation, InvitationStatus $status, array $attributes): Invitation
    {
        $invitation->update(['status' => $status, ...$attributes]);

        return $invitation->refresh();
    }
}
