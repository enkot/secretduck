<?php

use App\Enums\ChallengeType;
use App\Enums\InvitationStatus;
use App\Enums\TeamRole;
use App\Models\Challenge;
use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('unverified hosts cannot reach their invitations workspace', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('invitations.index', $user->currentTeam))
        ->assertRedirect(route('verification.notice'));
});

test('invitation workspace includes a pre-formatted event date', function () {
    $owner = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'team_id' => $owner->current_team_id,
        'starts_at' => '2027-07-17 16:30:00',
        'timezone' => 'Europe/Kyiv',
    ]);

    $this->actingAs($owner)
        ->get(route('invitations.index', $owner->currentTeam))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invitations/Index')
            ->where('invitations.0.publicId', $invitation->public_id)
            ->where('invitations.0.startsAtLabel', '17 Jul 2027, 19:30'),
        );
});

test('owners create and update team invitation drafts', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post(route('invitations.store', $owner->currentTeam), ['title' => 'Garden wedding'])
        ->assertRedirect();

    $invitation = Invitation::query()->sole();
    expect($invitation->team_id)->toBe($owner->current_team_id)
        ->and($invitation->status)->toBe(InvitationStatus::Draft)
        ->and($invitation->public_id)->toHaveLength(26);

    $this->actingAs($owner)
        ->patch(route('invitations.update', [$owner->currentTeam, $invitation]), [
            'host_names' => 'Emma & Daniel',
            'starts_at' => '2027-07-17T16:30',
            'timezone' => 'Europe/Kiev',
            'theme' => 'romantic',
        ])
        ->assertSessionHasNoErrors();

    $invitation->refresh();

    expect($invitation->host_names)->toBe('Emma & Daniel')
        ->and($invitation->starts_at?->format('Y-m-d H:i'))->toBe('2027-07-17 16:30')
        ->and($invitation->timezone)->toBe('Europe/Kyiv');
});

test('admins manage event invitations while ordinary members cannot', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $owner->currentTeam->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $owner->currentTeam->members()->attach($member, ['role' => TeamRole::Member->value]);
    $admin->switchTeam($owner->currentTeam);
    $member->switchTeam($owner->currentTeam);

    $this->actingAs($admin)
        ->post(route('invitations.store', $owner->currentTeam), ['title' => 'Admin event'])
        ->assertRedirect();

    $this->actingAs($member)
        ->post(route('invitations.store', $owner->currentTeam), ['title' => 'Member event'])
        ->assertForbidden();
});

test('publishing revalidates challenge event and active recipient invariants', function () {
    $owner = User::factory()->create();
    $invitation = Invitation::factory()->create(['team_id' => $owner->current_team_id, 'host_names' => null]);

    $this->actingAs($owner)
        ->post(route('invitations.publish', [$owner->currentTeam, $invitation]))
        ->assertSessionHasErrors(['host_names', 'challenge', 'recipients']);

    $invitation->update([
        'host_names' => 'Emma & Daniel',
        'starts_at' => now()->addMonth(),
        'timezone' => 'Europe/Kyiv',
        'teaser_text' => 'Solve this to reveal the details.',
    ]);
    Challenge::factory()->create(['invitation_id' => $invitation->id]);
    InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

    $this->actingAs($owner)
        ->post(route('invitations.publish', [$owner->currentTeam, $invitation]))
        ->assertSessionHasNoErrors();

    expect($invitation->refresh()->status)->toBe(InvitationStatus::Published);
});

test('published challenge configuration is immutable', function () {
    $owner = User::factory()->create();
    $invitation = Invitation::factory()->published()->create(['team_id' => $owner->current_team_id]);
    Challenge::factory()->create(['invitation_id' => $invitation->id]);

    $this->actingAs($owner)
        ->put(route('invitations.challenge.update', [$owner->currentTeam, $invitation]), [
            'type' => ChallengeType::Scratch->value,
            'configuration' => ['prompt' => 'Scratch', 'threshold' => 65],
        ])
        ->assertSessionHasErrors('challenge');

    expect($invitation->challenge->type)->toBe(ChallengeType::Trivia);
});

test('an invitation from another team is not exposed through nested host routes', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $invitation = Invitation::factory()->create(['team_id' => $other->current_team_id]);

    $this->actingAs($owner)
        ->get(route('invitations.show', [$owner->currentTeam, $invitation]))
        ->assertNotFound();
});
