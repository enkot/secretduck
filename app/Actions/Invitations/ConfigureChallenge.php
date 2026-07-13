<?php

namespace App\Actions\Invitations;

use App\Challenges\ChallengeManager;
use App\Enums\ChallengeType;
use App\Enums\InvitationStatus;
use App\Models\Challenge;
use App\Models\Invitation;
use Illuminate\Validation\ValidationException;

final readonly class ConfigureChallenge
{
    public function __construct(private ChallengeManager $manager) {}

    /** @param array<string, mixed> $configuration */
    public function handle(Invitation $invitation, ChallengeType $type, array $configuration, int $maxAttempts): Challenge
    {
        if ($invitation->status !== InvitationStatus::Draft) {
            throw ValidationException::withMessages(['challenge' => 'Published challenge configuration is immutable.']);
        }

        $normalized = $this->manager->driver($type)->normalizeHostConfiguration($configuration);

        return $invitation->challenge()->updateOrCreate([], [
            'type' => $type,
            'public_configuration' => $normalized['public'],
            'private_configuration' => $normalized['private'],
            'max_attempts' => $maxAttempts,
        ]);
    }
}
