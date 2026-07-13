<?php

namespace App\Http\Controllers\Guest;

use App\Actions\Guests\RecordInvitationEvent;
use App\Enums\InvitationEventType;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\Services\GuestAccessResolver;
use App\Services\RevealPayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RevealController extends Controller
{
    public function __invoke(
        Request $request,
        Invitation $invitation,
        GuestAccessResolver $resolver,
        RevealPayloadBuilder $builder,
        RecordInvitationEvent $recordEvent,
    ): JsonResponse {
        $session = $resolver->requireActive($request, $invitation);
        abort_unless($session->recipient->isUnlocked(), 404);

        DB::transaction(function () use ($session, $invitation, $recordEvent): void {
            $recipient = InvitationRecipient::query()->lockForUpdate()->findOrFail($session->recipient_id);
            if ($recipient->revealed_at === null) {
                $recipient->update(['revealed_at' => now()]);
                $recordEvent->handle(
                    $invitation,
                    InvitationEventType::InvitationRevealed,
                    $recipient,
                    $session,
                    "recipient:{$recipient->id}:revealed",
                );
            }
        });
        $session->recipient->refresh();

        return response()->json($builder->build($invitation, $session))
            ->header('Cache-Control', 'private, no-store');
    }
}
