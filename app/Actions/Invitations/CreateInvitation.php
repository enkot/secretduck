<?php

namespace App\Actions\Invitations;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Models\Team;
use Illuminate\Support\Str;

final class CreateInvitation
{
    /** @param array<string, mixed> $attributes */
    public function handle(Team $team, array $attributes): Invitation
    {
        return $team->invitations()->create([
            'public_id' => (string) Str::ulid(),
            'status' => InvitationStatus::Draft,
            'title' => $attributes['title'],
        ]);
    }
}
