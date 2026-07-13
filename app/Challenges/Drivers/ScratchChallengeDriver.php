<?php

namespace App\Challenges\Drivers;

use App\Challenges\ChallengeDriver;
use App\Enums\ChallengeType;
use App\Models\Challenge;
use App\Models\InvitationRecipient;
use App\ValueObjects\ChallengeResult;
use Illuminate\Validation\ValidationException;

final class ScratchChallengeDriver implements ChallengeDriver
{
    public function type(): ChallengeType
    {
        return ChallengeType::Scratch;
    }

    public function normalizeHostConfiguration(array $configuration): array
    {
        $threshold = (int) ($configuration['threshold'] ?? 65);

        if ($threshold < 50 || $threshold > 90) {
            throw ValidationException::withMessages(['configuration.threshold' => 'The scratch threshold must be between 50 and 90.']);
        }

        return [
            'public' => [
                'prompt' => mb_substr((string) ($configuration['prompt'] ?? 'Scratch to reveal your invitation'), 0, 160),
                'threshold' => $threshold,
            ],
            'private' => [],
        ];
    }

    public function publicState(Challenge $challenge, InvitationRecipient $recipient): array
    {
        return $challenge->public_configuration;
    }

    public function verify(Challenge $challenge, array $submission): ChallengeResult
    {
        if (($submission['thresholdReached'] ?? false) !== true) {
            throw ValidationException::withMessages(['thresholdReached' => 'Finish scratching or use the accessible reveal control.']);
        }

        return new ChallengeResult(true, 'You found it!');
    }
}
