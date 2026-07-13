<?php

use App\Enums\ChallengeType;
use App\Models\Invitation;
use App\Models\InvitationEvent;
use App\Models\InvitationRecipient;
use App\Models\Rsvp;
use App\Models\RsvpRevision;
use App\Models\User;
use Database\Seeders\QuestInviteDemoSeeder;

test('demo seeder creates the complete SecretDuck showcase', function () {
    $this->seed(QuestInviteDemoSeeder::class);

    $host = User::query()->where('email', 'host@secretduck.test')->firstOrFail();
    $challengeTypes = Invitation::query()
        ->with('challenge')
        ->get()
        ->pluck('challenge.type')
        ->all();

    expect($host->hasVerifiedEmail())->toBeTrue()
        ->and(Invitation::query()->count())->toBe(3)
        ->and($challengeTypes)->toContain(
            ChallengeType::Trivia,
            ChallengeType::Sudoku,
            ChallengeType::Scratch,
        )
        ->and(InvitationRecipient::query()->count())->toBe(5)
        ->and(InvitationRecipient::query()->whereNotNull('revoked_at')->count())->toBe(1)
        ->and(Rsvp::query()->count())->toBe(1)
        ->and(RsvpRevision::query()->count())->toBe(1)
        ->and(InvitationEvent::query()->count())->toBe(6);
});
