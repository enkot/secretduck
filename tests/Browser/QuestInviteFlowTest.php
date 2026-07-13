<?php

use App\Enums\ChallengeType;
use App\Enums\InvitationStatus;
use App\Models\Challenge;
use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\Models\User;

test('landing page is responsive accessible and free of JavaScript errors', function () {
    visit('/')
        ->resize(390, 844)
        ->assertSee('Complete a little challenge.')
        ->assertSee('Reveal the big invitation.')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
});

test('landing page how it works section uses the corresponding illustrations', function () {
    visit('/')
        ->resize(390, 844)
        ->assertSee('Host seals. Guest solves. Party happens.')
        ->assertVisible('img[src="/images/duck-challenge.png"]')
        ->assertVisible('img[src="/images/duck-link.png"]')
        ->assertVisible('img[src="/images/duck-watch.png"]')
        ->assertNoJavaScriptErrors();
});

test('public pages keep the default design tokens', function () {
    visit('/')
        ->assertNotPresent('[data-admin-area]')
        ->assertScript("getComputedStyle(document.body).getPropertyValue('--spacing').trim()", '0.25rem')
        ->assertScript("getComputedStyle(document.body).getPropertyValue('--text-sm').trim()", '0.875rem')
        ->assertNoJavaScriptErrors();
});

test('login and registration pages use larger form controls', function () {
    $page = visit(route('login'));

    $page
        ->assertScript("getComputedStyle(document.querySelector('#email')).height", '48px')
        ->assertScript("getComputedStyle(document.querySelector('#password')).height", '48px')
        ->assertScript("getComputedStyle(document.querySelector('[data-test=\"google-auth-button\"]')).height", '48px')
        ->assertScript("getComputedStyle(document.querySelector('[data-test=\"login-button\"]')).height", '48px')
        ->assertNoJavaScriptErrors();

    $page
        ->click('@register-link')
        ->assertPathIs('/register')
        ->assertScript("getComputedStyle(document.querySelector('#name')).height", '48px')
        ->assertScript("getComputedStyle(document.querySelector('#email')).height", '48px')
        ->assertScript("getComputedStyle(document.querySelector('#password')).height", '48px')
        ->assertScript("getComputedStyle(document.querySelector('#password_confirmation')).height", '48px')
        ->assertScript("getComputedStyle(document.querySelector('[data-test=\"google-auth-button\"]')).height", '48px')
        ->assertScript("getComputedStyle(document.querySelector('[data-test=\"register-user-button\"]')).height", '48px')
        ->assertNoJavaScriptErrors();
});

test('invitations workspace uses the SecretDuck top header navigation', function () {
    $host = User::factory()->create();

    $this->actingAs($host);

    visit(route('invitations.index', $host->currentTeam))
        ->resize(1280, 800)
        ->assertVisible('[data-test="app-top-header"]')
        ->assertSee('SecretDuck')
        ->assertVisible('img[src="/logo.png"]')
        ->assertSee('Invitations')
        ->assertDontSee('Dashboard')
        ->assertNotPresent('[data-sidebar="sidebar"]')
        ->assertNoJavaScriptErrors()
        ->resize(390, 844)
        ->assertVisible('[aria-label="Open navigation menu"]')
        ->click('[aria-label="Open navigation menu"]')
        ->assertSee('Navigation menu')
        ->assertSee('Invitations')
        ->assertNoAccessibilityIssues();
});

test('invitation dates hydrate consistently between the server and browser', function () {
    $host = User::factory()->create();

    Invitation::factory()->create([
        'team_id' => $host->current_team_id,
        'title' => 'Summer garden party',
        'starts_at' => '2027-07-17 16:30:00',
        'timezone' => 'Europe/Kyiv',
    ]);

    $this->actingAs($host);

    visit(route('invitations.index', $host->currentTeam))
        ->assertSee('Summer garden party')
        ->assertSee('17 Jul 2027, 19:30')
        ->assertNoJavaScriptErrors();
});

test('admin area uses larger design tokens', function () {
    $host = User::factory()->create();

    $this->actingAs($host);

    visit(route('invitations.index', $host->currentTeam))
        ->resize(1280, 900)
        ->assertPresent('[data-admin-area]')
        ->assertScript("getComputedStyle(document.body).getPropertyValue('--spacing').trim()", '0.275rem')
        ->assertScript("getComputedStyle(document.body).getPropertyValue('--text-sm').trim()", '1rem')
        ->assertNoJavaScriptErrors();
});

test('host can read the complete RSVP submitted by a recipient', function () {
    $host = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'team_id' => $host->current_team_id,
        'title' => 'Garden celebration',
    ]);
    $recipient = InvitationRecipient::factory()->create([
        'invitation_id' => $invitation->id,
        'name' => 'Original Recipient',
    ]);
    $recipient->rsvp()->create([
        'respondent_name' => 'Avery Guest',
        'response' => 'attending',
        'guest_count' => 2,
        'dietary_notes' => 'One vegan meal',
        'message' => 'We are delighted!',
        'submitted_at' => now(),
    ]);

    $this->actingAs($host);

    visit(route('invitations.show', [$host->currentTeam, $invitation]))
        ->assertSee('Guest responses')
        ->assertSee('Avery Guest')
        ->assertSee('Attending')
        ->assertSee('One vegan meal')
        ->assertSee('We are delighted!')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
});

test('host can save host names and event start from the invitation builder', function () {
    $host = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'team_id' => $host->current_team_id,
        'host_names' => null,
        'starts_at' => null,
        'timezone' => null,
    ]);

    $this->actingAs($host);

    visit(route('invitations.edit', [$host->currentTeam, $invitation]))
        ->fill('host_names', 'Taras & Katrusia')
        ->fill('starts_at', '2027-07-17T16:30')
        ->pressAndWaitFor('Save event')
        ->assertNoJavaScriptErrors();

    $invitation->refresh();

    expect($invitation->host_names)->toBe('Taras & Katrusia')
        ->and($invitation->starts_at?->format('Y-m-d H:i'))->toBe('2027-07-17 16:30')
        ->and($invitation->timezone)->not->toBeNull();
});

test('invitation builder only asks for essential details', function () {
    $host = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'team_id' => $host->current_team_id,
    ]);

    $this->actingAs($host);

    visit(route('invitations.edit', [$host->currentTeam, $invitation]))
        ->assertSee('Only the essential details guests need to know.')
        ->assertNotPresent('input[name="dress_code"]')
        ->assertNotPresent('input[name="rsvp_deadline_at"]')
        ->assertNotPresent('input[name="map_url"]')
        ->assertNotPresent('input[name="external_url"]')
        ->assertNotPresent('textarea[name="address"]')
        ->assertNotPresent('textarea[name="description"]')
        ->press('Appearance')
        ->assertNotPresent('input[name="accent_color"]')
        ->assertNotPresent('input[name="reveal_heading"]')
        ->assertNotPresent('input[name="success_message"]')
        ->press('Recipients')
        ->assertNotPresent('#recipient-email')
        ->assertNotPresent('#recipient-greeting')
        ->assertNotPresent('#recipient-guests')
        ->assertNoJavaScriptErrors();
});

test('host can use the scratch challenge without configuring advanced fields', function () {
    $host = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'team_id' => $host->current_team_id,
    ]);

    $this->actingAs($host);

    visit(route('invitations.edit', [$host->currentTeam, $invitation]))
        ->press('Challenge')
        ->press('Scratch')
        ->pressAndWaitFor('Use scratch challenge')
        ->assertNoJavaScriptErrors();

    $invitation->refresh()->load('challenge');

    expect($invitation->challenge->type)->toBe(ChallengeType::Scratch)
        ->and($invitation->challenge->public_configuration)->toMatchArray([
            'prompt' => 'Scratch to reveal your invitation',
            'threshold' => 65,
        ]);
});

test('invitation builder guides the host through each step', function () {
    $host = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'team_id' => $host->current_team_id,
    ]);

    $this->actingAs($host);

    visit(route('invitations.edit', [$host->currentTeam, $invitation]))
        ->assertSee('Event details')
        ->assertSee('Step 1 of 5')
        ->press('Next')
        ->assertSee('Appearance & reveal')
        ->assertSee('Private cover image')
        ->assertSee('Step 2 of 5')
        ->press('Next')
        ->assertSee('Challenge')
        ->assertSee('Step 3 of 5')
        ->press('Next')
        ->assertSee('Recipients')
        ->assertSee('Step 4 of 5')
        ->press('Next')
        ->assertSee('Review & publish')
        ->assertSee('Preview guest experience')
        ->assertSee('Step 5 of 5')
        ->press('Back')
        ->assertSee('Recipients')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
});

test('invitation builder highlights the current step in gold', function () {
    $host = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'team_id' => $host->current_team_id,
    ]);

    $this->actingAs($host);

    visit(route('invitations.edit', [$host->currentTeam, $invitation]))
        ->assertScript(
            "getComputedStyle(document.querySelector('[data-state=\"active\"] [data-test=\"invitation-step-indicator\"]')).backgroundColor",
            'rgb(250, 181, 43)',
        )
        ->press('Next')
        ->assertSee('Step 2 of 5')
        ->assertScript(
            "getComputedStyle(document.querySelector('[data-state=\"active\"] [data-test=\"invitation-step-indicator\"]')).backgroundColor",
            'rgb(250, 181, 43)',
        )
        ->assertNoJavaScriptErrors();
});

test('publish explains which invitation requirement is missing', function () {
    $host = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'team_id' => $host->current_team_id,
        'teaser_text' => null,
    ]);
    Challenge::factory()->create(['invitation_id' => $invitation->id]);
    InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

    $this->actingAs($host);

    visit(route('invitations.show', [$host->currentTeam, $invitation]))
        ->assertSee('Teaser needed')
        ->pressAndWaitFor('Publish invitation')
        ->assertSee('Invitation is not ready to publish')
        ->assertSee('Teaser text:')
        ->assertSee('This field is required before publishing.')
        ->assertNoJavaScriptErrors();

    expect($invitation->refresh()->status)->toBe(InvitationStatus::Draft);
});

test('scratch fallback authorizes unlocks and reveals without hidden content flashing early', function () {
    $invitation = Invitation::factory()->published()->create([
        'title' => 'Midnight Garden Party',
        'description' => 'Meet us beneath the lanterns.',
    ]);
    Challenge::factory()->create([
        'invitation_id' => $invitation->id,
        'type' => ChallengeType::Scratch,
        'public_configuration' => ['prompt' => 'Scratch the midnight sky', 'threshold' => 65],
        'private_configuration' => [],
    ]);
    $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id, 'name' => 'Mobile Guest']);

    $page = visit(route('guest.show', $invitation).'#t='.$recipient->token_ciphertext)
        ->resize(390, 844)
        ->assertDontSee('Meet us beneath the lanterns.')
        ->assertSee('Reveal without scratching')
        ->pressAndWaitFor('Reveal without scratching')
        ->assertSee('Midnight Garden Party')
        ->assertSee('Meet us beneath the lanterns.')
        ->assertNoJavaScriptErrors();

    expect($recipient->refresh()->challenge_completed_at)->not->toBeNull();
    $page->assertNoAccessibilityIssues();
});

test('trivia gives feedback then reveals only after the correct choice', function () {
    $invitation = Invitation::factory()->published()->create(['title' => 'Emma & Daniel']);
    Challenge::factory()->create(['invitation_id' => $invitation->id]);
    $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

    visit(route('guest.show', $invitation).'#t='.$recipient->token_ciphertext)
        ->assertSee('Where did we first meet?')
        ->radio('trivia-answer', 'a')
        ->pressAndWaitFor('Unlock invitation')
        ->assertSee('Not quite—try again.')
        ->radio('trivia-answer', 'b')
        ->pressAndWaitFor('Unlock invitation')
        ->assertSee('Emma & Daniel')
        ->assertNoJavaScriptErrors();
});
