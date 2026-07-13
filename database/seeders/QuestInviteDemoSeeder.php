<?php

namespace Database\Seeders;

use App\Challenges\ChallengeManager;
use App\Enums\ChallengeType;
use App\Enums\InvitationEventType;
use App\Enums\RsvpResponse;
use App\Enums\Theme;
use App\Models\Invitation;
use App\Models\InvitationEvent;
use App\Models\InvitationRecipient;
use App\Models\Rsvp;
use App\Models\User;
use App\ValueObjects\RecipientToken;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class QuestInviteDemoSeeder extends Seeder
{
    public function run(ChallengeManager $challenges): void
    {
        if (app()->isProduction() && ! config('questinvite.allow_demo_seeding')) {
            throw new RuntimeException('Demo seeding is disabled in production.');
        }

        $host = User::factory()->create([
            'name' => 'Olivia Hart',
            'email' => 'host@secretduck.test',
            'email_verified_at' => now(),
        ]);
        $team = $host->currentTeam;
        $wedding = $this->invitation($team->id, 'Emma & Daniel', Theme::Romantic, 'trivia');
        $trivia = $challenges->driver(ChallengeType::Trivia)->normalizeHostConfiguration([
            'question' => 'Where did Emma and Daniel first meet?',
            'options' => [
                ['id' => 'coffee', 'label' => 'A tiny coffee shop'],
                ['id' => 'bus', 'label' => 'At a rainy bus stop'],
                ['id' => 'dinner', 'label' => 'A friend’s dinner'],
            ],
            'correctOptionId' => 'bus',
            'successMessage' => 'Exactly. Now for the big reveal!',
            'failureMessage' => 'Not quite—follow the rain.',
        ]);
        $wedding->challenge()->create(['type' => ChallengeType::Trivia, 'public_configuration' => $trivia['public'], 'private_configuration' => $trivia['private'], 'max_attempts' => 5]);

        $emma = $this->recipient($wedding, 'Emma Stone', 'emma@example.test', true);
        $emma->update(['revealed_at' => now()->subMinutes(40)]);
        $rsvp = Rsvp::factory()->create(['recipient_id' => $emma->id, 'respondent_name' => 'Emma Stone', 'response' => RsvpResponse::Attending, 'guest_count' => 2, 'message' => 'We cannot wait!']);
        $rsvp->revisions()->create(['respondent_name' => 'Emma Stone', 'response' => RsvpResponse::Attending, 'guest_count' => 2, 'dietary_notes' => 'One vegetarian meal', 'message' => 'We cannot wait!']);
        $this->recipient($wedding, 'Lucas Reed', 'lucas@example.test');
        $mia = $this->recipient($wedding, 'Mia Clark', 'mia@example.test', true);
        $mia->update(['revoked_at' => now()]);

        $sudokuInvitation = $this->invitation($team->id, 'A Garden Celebration', Theme::Elegant, 'sudoku');
        $sudoku = $challenges->driver(ChallengeType::Sudoku)->normalizeHostConfiguration(['template' => 'garden-path', 'allowedHints' => 2]);
        $sudokuInvitation->challenge()->create(['type' => ChallengeType::Sudoku, 'public_configuration' => $sudoku['public'], 'private_configuration' => $sudoku['private'], 'max_attempts' => 5]);
        $this->recipient($sudokuInvitation, 'Sudoku Guest', 'sudoku@example.test');

        $scratchInvitation = $this->invitation($team->id, 'Secret Rooftop Party', Theme::Playful, 'scratch');
        $scratch = $challenges->driver(ChallengeType::Scratch)->normalizeHostConfiguration(['prompt' => 'Scratch away the midnight sky', 'threshold' => 65]);
        $scratchInvitation->challenge()->create(['type' => ChallengeType::Scratch, 'public_configuration' => $scratch['public'], 'private_configuration' => $scratch['private'], 'max_attempts' => 5]);
        $this->recipient($scratchInvitation, 'Scratch Guest', 'scratch@example.test');
    }

    private function invitation(int $teamId, string $title, Theme $theme, string $kind): Invitation
    {
        return Invitation::factory()->published()->create([
            'team_id' => $teamId,
            'title' => $title,
            'theme' => $theme,
            'host_names' => 'Olivia & the SecretDuck team',
            'starts_at' => now()->addMonths(2),
            'timezone' => 'Europe/Kyiv',
            'rsvp_deadline_at' => now()->addMonth(),
            'venue_name' => 'The Glasshouse',
            'address' => '14 Garden Lane, Kyiv',
            'map_url' => 'https://maps.google.com/',
            'external_url' => 'https://example.com/',
            'teaser_text' => "Complete this {$kind} quest to reveal the invitation.",
        ]);
    }

    private function recipient(Invitation $invitation, string $name, string $email, bool $unlocked = false): InvitationRecipient
    {
        $token = RecipientToken::generate();
        $recipient = InvitationRecipient::factory()->create([
            'invitation_id' => $invitation->id,
            'public_id' => (string) Str::ulid(),
            'name' => $name,
            'email' => $email,
            'token_hash' => $token->hash(),
            'token_ciphertext' => $token->value,
            'opened_at' => $unlocked ? now()->subHour() : null,
            'challenge_started_at' => $unlocked ? now()->subMinutes(55) : null,
            'challenge_completed_at' => $unlocked ? now()->subMinutes(50) : null,
        ]);

        if ($unlocked) {
            foreach ([InvitationEventType::LinkOpened, InvitationEventType::ChallengeStarted, InvitationEventType::ChallengeCompleted] as $type) {
                InvitationEvent::factory()->create(['invitation_id' => $invitation->id, 'recipient_id' => $recipient->id, 'type' => $type]);
            }
        }

        return $recipient;
    }
}
