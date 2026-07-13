<?php

namespace App\Http\Controllers\Host;

use App\Actions\Invitations\ChangeInvitationStatus;
use App\Actions\Invitations\PublishInvitation;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InvitationStateController extends Controller
{
    public function publish(Request $request, string $current_team, Invitation $invitation, PublishInvitation $action): RedirectResponse
    {
        $this->authorizeScoped($request, $invitation, 'publish');
        $action->handle($invitation);

        return back()->with('success', 'Invitation published.');
    }

    public function pause(Request $request, string $current_team, Invitation $invitation, ChangeInvitationStatus $action): RedirectResponse
    {
        $this->authorizeScoped($request, $invitation, 'pause');
        $action->pause($invitation);

        return back()->with('success', 'Guest access paused.');
    }

    public function resume(Request $request, string $current_team, Invitation $invitation, PublishInvitation $action): RedirectResponse
    {
        $this->authorizeScoped($request, $invitation, 'publish');
        $action->handle($invitation);

        return back()->with('success', 'Invitation resumed.');
    }

    public function archive(Request $request, string $current_team, Invitation $invitation, ChangeInvitationStatus $action): RedirectResponse
    {
        $this->authorizeScoped($request, $invitation, 'archive');
        $action->archive($invitation);

        return to_route('invitations.index', ['current_team' => $request->user()->currentTeam->slug])->with('success', 'Invitation archived.');
    }

    private function authorizeScoped(Request $request, Invitation $invitation, string $ability): void
    {
        abort_unless($invitation->team_id === $request->user()->current_team_id, 404);
        Gate::authorize($ability, $invitation);
    }
}
