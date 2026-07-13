<?php

namespace App\Actions\Guests;

use App\Challenges\ChallengeManager;
use App\Enums\InvitationEventType;
use App\Models\GuestSession;
use App\Models\InvitationRecipient;
use App\ValueObjects\ChallengeResult;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

final readonly class SubmitChallenge
{
    public function __construct(
        private ChallengeManager $manager,
        private StartChallenge $startChallenge,
        private RecordInvitationEvent $recordEvent,
    ) {}

    /** @param array<string, mixed> $submission */
    public function handle(GuestSession $session, array $submission): ChallengeResult
    {
        $this->startChallenge->handle($session);

        return DB::transaction(function () use ($session, $submission): ChallengeResult {
            $recipient = InvitationRecipient::query()
                ->with('invitation.challenge')
                ->lockForUpdate()
                ->findOrFail($session->recipient_id);

            abort_unless($recipient->hasActiveAccess(), 404);

            if ($recipient->isUnlocked()) {
                return new ChallengeResult(true, $recipient->invitation->success_message ?? 'Invitation unlocked!');
            }

            if ($recipient->challenge_locked_until?->isFuture()) {
                $retryAfter = max(1, (int) ceil($recipient->challenge_locked_until->diffInSeconds(now())));

                throw new TooManyRequestsHttpException(
                    $retryAfter,
                    'Too many attempts. Please try again shortly.',
                );
            }

            $challenge = $recipient->invitation->challenge;
            abort_if($challenge === null, 404);
            $result = $this->manager->driver($challenge->type)->verify($challenge, $submission);

            if ($result->completed) {
                $recipient->update([
                    'challenge_completed_at' => now(),
                    'failed_attempts' => 0,
                    'attempt_window_started_at' => null,
                    'challenge_locked_until' => null,
                ]);
                $this->recordEvent->handle(
                    $recipient->invitation,
                    InvitationEventType::ChallengeCompleted,
                    $recipient,
                    $session,
                    "recipient:{$recipient->id}:challenge-completed",
                    ['challengeType' => $challenge->type->value],
                );

                return $result;
            }

            $now = now();
            $windowMinutes = config('questinvite.attempt_window_minutes');
            $windowExpired = $recipient->attempt_window_started_at === null
                || $recipient->attempt_window_started_at->copy()->addMinutes($windowMinutes)->isPast();
            $failedAttempts = $windowExpired ? 1 : $recipient->failed_attempts + 1;
            $maxAttempts = $challenge->max_attempts;
            $lockedUntil = $failedAttempts >= $maxAttempts ? $now->copy()->addMinutes(config('questinvite.lock_minutes')) : null;

            $recipient->update([
                'attempt_window_started_at' => $windowExpired ? $now : $recipient->attempt_window_started_at,
                'failed_attempts' => $failedAttempts,
                'challenge_locked_until' => $lockedUntil,
            ]);
            $this->recordEvent->handle(
                $recipient->invitation,
                InvitationEventType::ChallengeFailed,
                $recipient,
                $session,
                metadata: ['challengeType' => $challenge->type->value],
            );

            return new ChallengeResult(false, $result->message, max(0, $maxAttempts - $failedAttempts), $lockedUntil);
        });
    }
}
