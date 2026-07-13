<?php

namespace App\Actions\Invitations;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PublishInvitation
{
    public function handle(Invitation $invitation): Invitation
    {
        return DB::transaction(function () use ($invitation): Invitation {
            $locked = Invitation::query()->lockForUpdate()->findOrFail($invitation->id);
            $locked->load('challenge');
            $errors = [];

            foreach (['title', 'host_names', 'starts_at', 'timezone', 'teaser_text'] as $field) {
                if (blank($locked->{$field})) {
                    $errors[$field] = ['This field is required before publishing.'];
                }
            }

            if ($locked->challenge === null) {
                $errors['challenge'] = ['Configure a challenge before publishing.'];
            }

            if (! $locked->recipients()->whereNull('revoked_at')->exists()) {
                $errors['recipients'] = ['Add at least one active recipient before publishing.'];
            }

            if ($locked->rsvp_deadline_at?->isAfter($locked->starts_at)) {
                $errors['rsvp_deadline_at'] = ['The RSVP deadline must be before the event starts.'];
            }

            if ($locked->effectiveAccessExpiresAt()?->isBefore($locked->starts_at)) {
                $errors['access_expires_at'] = ['Guest access must remain available through the event start.'];
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $locked->update([
                'status' => InvitationStatus::Published,
                'published_at' => $locked->published_at ?? now(),
                'paused_at' => null,
            ]);

            return $locked->refresh();
        });
    }
}
