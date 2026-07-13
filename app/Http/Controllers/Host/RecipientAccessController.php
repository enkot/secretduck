<?php

namespace App\Http\Controllers\Host;

use App\Actions\Invitations\RotateRecipientToken;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\ValueObjects\RecipientToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RecipientAccessController extends Controller
{
    public function link(Request $request, string $current_team, Invitation $invitation, InvitationRecipient $recipient): JsonResponse
    {
        $this->authorizeScoped($request, $invitation, $recipient);

        return $this->linkResponse($invitation, new RecipientToken($recipient->token_ciphertext));
    }

    public function regenerate(Request $request, string $current_team, Invitation $invitation, InvitationRecipient $recipient, RotateRecipientToken $action): JsonResponse
    {
        $this->authorizeScoped($request, $invitation, $recipient);

        return $this->linkResponse($invitation, $action->regenerate($recipient));
    }

    public function revoke(Request $request, string $current_team, Invitation $invitation, InvitationRecipient $recipient, RotateRecipientToken $action): JsonResponse
    {
        $this->authorizeScoped($request, $invitation, $recipient);
        $action->revoke($recipient);

        return response()->json(['revoked' => true])->header('Cache-Control', 'private, no-store');
    }

    public function reactivate(Request $request, string $current_team, Invitation $invitation, InvitationRecipient $recipient, RotateRecipientToken $action): JsonResponse
    {
        $this->authorizeScoped($request, $invitation, $recipient);

        return $this->linkResponse($invitation, $action->reactivate($recipient));
    }

    private function authorizeScoped(Request $request, Invitation $invitation, InvitationRecipient $recipient): void
    {
        abort_unless($invitation->team_id === $request->user()->current_team_id && $recipient->invitation_id === $invitation->id, 404);
        Gate::authorize('update', $invitation);
    }

    private function linkResponse(Invitation $invitation, RecipientToken $token): JsonResponse
    {
        return response()->json(['url' => $token->fragmentUrl(route('guest.show', $invitation))])
            ->header('Cache-Control', 'private, no-store');
    }
}
