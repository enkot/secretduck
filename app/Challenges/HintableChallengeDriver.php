<?php

namespace App\Challenges;

use App\Models\Challenge;
use App\Models\InvitationRecipient;

interface HintableChallengeDriver extends ChallengeDriver
{
    /** @param array<string, mixed> $submission
     * @return array{index: int, value: int}
     */
    public function hint(Challenge $challenge, InvitationRecipient $recipient, array $submission): array;
}
