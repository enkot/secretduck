<?php

namespace App\Services;

use App\Challenges\ChallengeManager;
use App\Enums\InvitationStatus;
use App\Models\GuestSession;
use App\Models\Invitation;
use Illuminate\Support\Facades\Storage;
use Throwable;

final readonly class GuestStateBuilder
{
    public function __construct(private ChallengeManager $manager) {}

    /** @return array<string, mixed> */
    public function build(Invitation $invitation, ?GuestSession $session): array
    {
        if ($session === null) {
            return ['availability' => 'authorization_required'];
        }

        $recipient = $session->recipient;
        $availability = match (true) {
            $invitation->status === InvitationStatus::Draft => 'unpublished',
            $invitation->status === InvitationStatus::Paused => 'paused',
            $invitation->status === InvitationStatus::Archived => 'archived',
            $invitation->isExpired() => 'expired',
            default => 'available',
        };

        $state = [
            'availability' => $availability,
            'recipient' => ['name' => $recipient->name, 'greeting' => $recipient->greeting],
        ];

        if ($availability !== 'available') {
            return $state;
        }

        $challenge = $invitation->challenge;
        abort_if($challenge === null, 404);

        return [
            ...$state,
            'theme' => [
                'key' => $invitation->theme->value,
                'accentColor' => $invitation->accent_color,
                'coverImageUrl' => $this->coverUrl($invitation),
            ],
            'teaserText' => $invitation->teaser_text,
            'challenge' => [
                'type' => $challenge->type->value,
                'publicConfiguration' => $this->manager->driver($challenge->type)->publicState($challenge, $recipient),
                'attemptsRemaining' => max(0, $challenge->max_attempts - $recipient->failed_attempts),
                'lockedUntil' => $recipient->challenge_locked_until?->toIso8601String(),
            ],
            'unlocked' => $recipient->isUnlocked(),
            'rsvpSubmitted' => $recipient->rsvp !== null,
        ];
    }

    private function coverUrl(Invitation $invitation): ?string
    {
        if ($invitation->cover_image_path === null) {
            return null;
        }

        try {
            return Storage::disk(config('questinvite.cover_disk'))->temporaryUrl(
                $invitation->cover_image_path,
                now()->addMinutes(15),
            );
        } catch (Throwable) {
            return null;
        }
    }
}
