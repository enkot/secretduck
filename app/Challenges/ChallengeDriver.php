<?php

namespace App\Challenges;

use App\Enums\ChallengeType;
use App\Models\Challenge;
use App\Models\InvitationRecipient;
use App\ValueObjects\ChallengeResult;

interface ChallengeDriver
{
    public function type(): ChallengeType;

    /** @param array<string, mixed> $configuration
     * @return array{public: array<string, mixed>, private: array<string, mixed>}
     */
    public function normalizeHostConfiguration(array $configuration): array;

    /** @return array<string, mixed> */
    public function publicState(Challenge $challenge, InvitationRecipient $recipient): array;

    /** @param array<string, mixed> $submission */
    public function verify(Challenge $challenge, array $submission): ChallengeResult;
}
