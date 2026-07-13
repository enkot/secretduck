<?php

use App\Enums\ChallengeType;
use App\Enums\InvitationStatus;
use App\Models\Challenge;
use App\Models\Invitation;
use App\Models\InvitationRecipient;

function guestSecurityFixture(): array
{
    $invitation = Invitation::factory()->published()->create([
        'title' => 'Hidden Moonlight Wedding',
        'description' => 'The secret ceremony is beside the old observatory.',
        'starts_at' => '2027-07-17 16:30:00',
        'rsvp_deadline_at' => '2027-07-01 12:00:00',
        'timezone' => 'Europe/Kyiv',
    ]);
    Challenge::factory()->create([
        'invitation_id' => $invitation->id,
        'public_configuration' => [
            'question' => 'Which season?',
            'options' => [
                ['id' => 'winter-option', 'label' => 'Winter'],
                ['id' => 'summer-option', 'label' => 'Summer'],
            ],
            'successMessage' => 'Correct!',
            'failureMessage' => 'Try again.',
        ],
        'private_configuration' => ['correctOptionId' => 'summer-option'],
    ]);
    $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id, 'name' => 'Avery Guest']);

    return compact('invitation', 'recipient');
}

test('initial guest HTML contains no invitation payload or private answer', function () {
    ['invitation' => $invitation] = guestSecurityFixture();

    $response = $this->get(route('guest.show', $invitation));

    $response->assertOk()
        ->assertDontSee('Hidden Moonlight Wedding')
        ->assertDontSee('secret ceremony')
        ->assertDontSee('correctOptionId')
        ->assertSee('authorization_required');
});

test('valid recipient token creates a secure returning browser session with safe state only', function () {
    ['invitation' => $invitation, 'recipient' => $recipient] = guestSecurityFixture();

    $response = $this->postJson(route('guest.authorize', $invitation), ['token' => $recipient->token_ciphertext]);

    $response->assertOk()
        ->assertJsonPath('availability', 'available')
        ->assertJsonPath('recipient.name', 'Avery Guest')
        ->assertJsonPath('challenge.type', ChallengeType::Trivia->value)
        ->assertJsonMissing(['correctOptionId'])
        ->assertDontSee('Hidden Moonlight Wedding')
        ->assertDontSee('secret ceremony')
        ->assertHeader('Cache-Control', 'no-store, private');
    $cookie = $response->getCookie(config('questinvite.guest_cookie'));
    expect($cookie)->not->toBeNull()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getPath())->toBe('/open')
        ->and($cookie->getSameSite())->toBe('lax');
    expect($recipient->refresh()->opened_at)->not->toBeNull()
        ->and($recipient->guestSessions)->toHaveCount(1);
});

test('invalid and revoked tokens receive the same generic not found response', function () {
    ['invitation' => $invitation, 'recipient' => $recipient] = guestSecurityFixture();

    $this->postJson(route('guest.authorize', $invitation), ['token' => str_repeat('x', 43)])->assertNotFound();
    $recipient->update(['revoked_at' => now()]);
    $this->postJson(route('guest.authorize', $invitation), ['token' => $recipient->token_ciphertext])->assertNotFound();
});

test('challenge completion and reveal are separate authorized responses', function () {
    ['invitation' => $invitation, 'recipient' => $recipient] = guestSecurityFixture();
    $authorization = $this->postJson(route('guest.authorize', $invitation), ['token' => $recipient->token_ciphertext]);
    $browserToken = $authorization->getCookie(config('questinvite.guest_cookie'))->getValue();
    $this->withCredentials()->withCookie(config('questinvite.guest_cookie'), $browserToken);

    $this->getJson(route('guest.reveal', $invitation))->assertNotFound();
    $this->postJson(route('guest.challenge.submit', $invitation), ['optionId' => 'winter-option'])
        ->assertOk()->assertJsonPath('completed', false);
    $this->postJson(route('guest.challenge.submit', $invitation), ['optionId' => 'summer-option'])
        ->assertOk()->assertJsonPath('completed', true)->assertDontSee('Hidden Moonlight Wedding');

    $this->getJson(route('guest.reveal', $invitation))
        ->assertOk()
        ->assertJsonPath('title', 'Hidden Moonlight Wedding')
        ->assertJsonPath('description', 'The secret ceremony is beside the old observatory.')
        ->assertJsonPath('startsAtLabel', '17 Jul 2027, 19:30')
        ->assertJsonPath('rsvp.deadlineLabel', '1 Jul 2027')
        ->assertHeader('Cache-Control', 'no-store, private');

    expect($recipient->refresh()->challenge_completed_at)->not->toBeNull()
        ->and($recipient->revealed_at)->not->toBeNull()
        ->and($invitation->events()->where('type', 'challenge_completed')->count())->toBe(1);
});

test('duplicate successful challenge submissions are idempotent', function () {
    ['invitation' => $invitation, 'recipient' => $recipient] = guestSecurityFixture();
    $authorization = $this->postJson(route('guest.authorize', $invitation), ['token' => $recipient->token_ciphertext]);
    $this->withCredentials()->withCookie(config('questinvite.guest_cookie'), $authorization->getCookie(config('questinvite.guest_cookie'))->getValue());

    $this->postJson(route('guest.challenge.submit', $invitation), ['optionId' => 'summer-option'])->assertJsonPath('completed', true);
    $completedAt = $recipient->refresh()->challenge_completed_at;
    $this->postJson(route('guest.challenge.submit', $invitation), ['optionId' => 'summer-option'])->assertJsonPath('completed', true);

    expect($recipient->refresh()->challenge_completed_at->equalTo($completedAt))->toBeTrue()
        ->and($invitation->events()->where('type', 'challenge_completed')->count())->toBe(1);
});

test('a browser session cannot be replayed against another invitation', function () {
    ['invitation' => $invitation, 'recipient' => $recipient] = guestSecurityFixture();
    $otherInvitation = Invitation::factory()->published()->create();
    Challenge::factory()->create(['invitation_id' => $otherInvitation->id]);
    $authorization = $this->postJson(route('guest.authorize', $invitation), ['token' => $recipient->token_ciphertext]);
    $this->withCredentials()->withCookie(config('questinvite.guest_cookie'), $authorization->getCookie(config('questinvite.guest_cookie'))->getValue());

    $this->postJson(route('guest.challenge.start', $otherInvitation))->assertNotFound();
    $this->getJson(route('guest.reveal', $otherInvitation))->assertNotFound();
});

test('paused archived expired and revoked access is rechecked on every guest endpoint', function (InvitationStatus|string $state) {
    ['invitation' => $invitation, 'recipient' => $recipient] = guestSecurityFixture();
    $authorization = $this->postJson(route('guest.authorize', $invitation), ['token' => $recipient->token_ciphertext]);
    $this->withCredentials()->withCookie(config('questinvite.guest_cookie'), $authorization->getCookie(config('questinvite.guest_cookie'))->getValue());

    match ($state) {
        InvitationStatus::Paused => $invitation->update(['status' => InvitationStatus::Paused]),
        InvitationStatus::Archived => $invitation->update(['status' => InvitationStatus::Archived]),
        'expired' => $invitation->update(['access_expires_at' => now()->subMinute()]),
        'revoked' => $recipient->update(['revoked_at' => now()]),
    };

    $this->postJson(route('guest.challenge.start', $invitation))->assertNotFound();
    $this->getJson(route('guest.reveal', $invitation))->assertNotFound();
})->with([InvitationStatus::Paused, InvitationStatus::Archived, 'expired', 'revoked']);
