<?php

namespace App\Http\Controllers\Guest;

use App\Actions\Guests\SubmitRsvp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\SubmitRsvpRequest;
use App\Models\Invitation;
use App\Services\GuestAccessResolver;
use Illuminate\Http\JsonResponse;

class RsvpController extends Controller
{
    public function __invoke(SubmitRsvpRequest $request, Invitation $invitation, GuestAccessResolver $resolver, SubmitRsvp $action): JsonResponse
    {
        $rsvp = $action->handle($resolver->requireActive($request, $invitation), $request->validated());

        return response()->json([
            'saved' => true,
            'response' => $rsvp->response->value,
            'guestCount' => $rsvp->guest_count,
            'submittedAt' => $rsvp->submitted_at->toIso8601String(),
        ])->header('Cache-Control', 'private, no-store');
    }
}
