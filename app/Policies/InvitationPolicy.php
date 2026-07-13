<?php

namespace App\Policies;

use App\Enums\InvitationStatus;
use App\Enums\TeamPermission;
use App\Models\Invitation;
use App\Models\Team;
use App\Models\User;

class InvitationPolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $this->manages($user, $team);
    }

    public function view(User $user, Invitation $invitation): bool
    {
        return $this->manages($user, $invitation->team);
    }

    public function create(User $user, Team $team): bool
    {
        return $this->manages($user, $team);
    }

    public function update(User $user, Invitation $invitation): bool
    {
        return $this->view($user, $invitation) && $invitation->status !== InvitationStatus::Archived;
    }

    public function publish(User $user, Invitation $invitation): bool
    {
        return $this->update($user, $invitation)
            && in_array($invitation->status, [InvitationStatus::Draft, InvitationStatus::Paused], true);
    }

    public function pause(User $user, Invitation $invitation): bool
    {
        return $this->view($user, $invitation) && $invitation->status === InvitationStatus::Published;
    }

    public function archive(User $user, Invitation $invitation): bool
    {
        return $this->view($user, $invitation) && $invitation->status !== InvitationStatus::Archived;
    }

    public function viewAnalytics(User $user, Invitation $invitation): bool
    {
        return $this->view($user, $invitation);
    }

    private function manages(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManageEventInvitations);
    }
}
