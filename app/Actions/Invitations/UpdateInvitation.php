<?php

namespace App\Actions\Invitations;

use App\Models\Invitation;

final class UpdateInvitation
{
    /** @param array<string, mixed> $attributes */
    public function handle(Invitation $invitation, array $attributes): Invitation
    {
        $invitation->update($attributes);

        return $invitation->refresh();
    }
}
