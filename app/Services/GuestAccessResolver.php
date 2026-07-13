<?php

namespace App\Services;

use App\Models\GuestSession;
use App\Models\Invitation;
use Illuminate\Http\Request;

final class GuestAccessResolver
{
    public function resolve(Request $request, Invitation $invitation, bool $required = true): ?GuestSession
    {
        $rawToken = $request->cookie(config('questinvite.guest_cookie'));

        if (! is_string($rawToken) || $rawToken === '') {
            abort_if($required, 404);

            return null;
        }

        $session = GuestSession::query()
            ->with(['recipient.invitation.challenge', 'recipient.rsvp'])
            ->where('browser_token_hash', hash('sha256', $rawToken))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($session === null
            || $session->recipient->invitation_id !== $invitation->id
            || ! $session->isActiveFor($session->recipient)
        ) {
            abort_if($required, 404);

            return null;
        }

        $session->forceFill(['last_seen_at' => now()])->save();

        return $session;
    }

    public function requireActive(Request $request, Invitation $invitation): GuestSession
    {
        $session = $this->resolve($request, $invitation);

        abort_unless($session?->recipient->hasActiveAccess(), 404);

        return $session;
    }
}
