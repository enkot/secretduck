<?php

namespace App\Actions\Guests;

use App\Challenges\ChallengeManager;
use App\Enums\ChallengeType;
use App\Models\GuestSession;
use App\Models\InvitationRecipient;
use Illuminate\Support\Facades\DB;

final readonly class GiveSudokuHint
{
    public function __construct(private ChallengeManager $manager) {}

    /** @param array<string, mixed> $submission
     * @return array{index: int, value: int, hintsRemaining: int}
     */
    public function handle(GuestSession $session, array $submission): array
    {
        return DB::transaction(function () use ($session, $submission): array {
            $recipient = InvitationRecipient::query()
                ->with('invitation.challenge')
                ->lockForUpdate()
                ->findOrFail($session->recipient_id);
            abort_unless($recipient->hasActiveAccess() && ! $recipient->isUnlocked(), 404);
            $challenge = $recipient->invitation->challenge;
            abort_unless($challenge?->type === ChallengeType::Sudoku, 404);
            $hint = $this->manager->hintableDriver($challenge->type)->hint($challenge, $recipient, $submission);
            $recipient->increment('hints_used');

            return [
                ...$hint,
                'hintsRemaining' => max(0, (int) $challenge->public_configuration['allowedHints'] - $recipient->hints_used),
            ];
        });
    }
}
