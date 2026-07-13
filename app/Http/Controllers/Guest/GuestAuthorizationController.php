<?php

namespace App\Http\Controllers\Guest;

use App\Actions\Guests\AuthorizeGuest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\AuthorizeGuestRequest;
use App\Models\Invitation;
use App\Services\GuestStateBuilder;
use Illuminate\Http\JsonResponse;

class GuestAuthorizationController extends Controller
{
    public function __invoke(AuthorizeGuestRequest $request, Invitation $invitation, AuthorizeGuest $action, GuestStateBuilder $builder): JsonResponse
    {
        abort_unless($invitation->team()->exists(), 404);
        $authorized = $action->handle($invitation, $request->validated('token'));
        $minutes = config('questinvite.guest_session_days') * 1440;
        $cookie = cookie(
            config('questinvite.guest_cookie'),
            $authorized['browserToken']->value,
            $minutes,
            '/open',
            null,
            (bool) config('session.secure'),
            true,
            false,
            'lax',
        );

        return response()->json($builder->build($invitation, $authorized['session']))
            ->withCookie($cookie)
            ->header('Cache-Control', 'private, no-store');
    }
}
