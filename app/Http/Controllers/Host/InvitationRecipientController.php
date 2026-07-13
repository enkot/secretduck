<?php

namespace App\Http\Controllers\Host;

use App\Actions\Invitations\CreateRecipient;
use App\Http\Controllers\Controller;
use App\Http\Requests\Host\StoreRecipientRequest;
use App\Http\Requests\Host\UpdateRecipientRequest;
use App\Models\Invitation;
use App\Models\InvitationRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class InvitationRecipientController extends Controller
{
    public function store(StoreRecipientRequest $request, string $current_team, Invitation $invitation, CreateRecipient $action): JsonResponse
    {
        $this->authorizeScoped($request, $invitation);
        $created = $action->handle($invitation, $request->validated());
        $url = $created['token']->fragmentUrl(route('guest.show', $invitation));

        return response()->json([
            'recipient' => ['publicId' => $created['recipient']->public_id, 'name' => $created['recipient']->name],
            'url' => $url,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function update(UpdateRecipientRequest $request, string $current_team, Invitation $invitation, InvitationRecipient $recipient): RedirectResponse
    {
        $this->authorizeScoped($request, $invitation, $recipient);
        $recipient->update($request->validated());

        return back()->with('success', 'Recipient updated.');
    }

    private function authorizeScoped(StoreRecipientRequest|UpdateRecipientRequest $request, Invitation $invitation, ?InvitationRecipient $recipient = null): void
    {
        abort_unless($invitation->team_id === $request->user()->current_team_id, 404);
        abort_if($recipient !== null && $recipient->invitation_id !== $invitation->id, 404);
        Gate::authorize('update', $invitation);
    }
}
