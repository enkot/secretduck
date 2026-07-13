<?php

namespace App\Http\Controllers\Guest;

use App\Actions\Guests\RecordInvitationEvent;
use App\Enums\InvitationEventType;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Services\GuestAccessResolver;
use App\Services\IcsCalendarGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvitationUtilityController extends Controller
{
    public function calendar(
        Request $request,
        Invitation $invitation,
        GuestAccessResolver $resolver,
        IcsCalendarGenerator $generator,
        RecordInvitationEvent $events,
    ): Response {
        $session = $resolver->requireActive($request, $invitation);
        abort_unless($session->recipient->isUnlocked(), 404);
        $events->handle($invitation, InvitationEventType::CalendarOpened, $session->recipient, $session);

        return response($generator->generate($invitation), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="invitation.ics"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function map(Request $request, Invitation $invitation, GuestAccessResolver $resolver, RecordInvitationEvent $events): RedirectResponse
    {
        return $this->redirect($request, $invitation, $resolver, $events, InvitationEventType::MapOpened, $invitation->map_url);
    }

    public function website(Request $request, Invitation $invitation, GuestAccessResolver $resolver, RecordInvitationEvent $events): RedirectResponse
    {
        return $this->redirect($request, $invitation, $resolver, $events, InvitationEventType::WebsiteOpened, $invitation->external_url);
    }

    private function redirect(
        Request $request,
        Invitation $invitation,
        GuestAccessResolver $resolver,
        RecordInvitationEvent $events,
        InvitationEventType $type,
        ?string $url,
    ): RedirectResponse {
        $session = $resolver->requireActive($request, $invitation);
        abort_unless($session->recipient->isUnlocked() && $url !== null, 404);
        $events->handle($invitation, $type, $session->recipient, $session);

        return redirect()->away($url);
    }
}
