<?php

use App\Enums\RsvpResponse;
use App\Models\Challenge;
use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\Models\User;
use App\Services\InvitationAnalytics;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

function authorizedUnlockedGuest($test): array
{
    $invitation = Invitation::factory()->published()->create([
        'starts_at' => now()->addMonth(),
        'rsvp_deadline_at' => now()->addWeek(),
        'map_url' => 'https://maps.example.test/place',
        'external_url' => 'https://event.example.test',
    ]);
    Challenge::factory()->create(['invitation_id' => $invitation->id]);
    $recipient = InvitationRecipient::factory()->unlocked()->create(['invitation_id' => $invitation->id, 'max_guests' => 3]);
    $authorization = $test->postJson(route('guest.authorize', $invitation), ['token' => $recipient->token_ciphertext]);
    $test->withCredentials()->withCookie(config('questinvite.guest_cookie'), $authorization->getCookie(config('questinvite.guest_cookie'))->getValue());

    return compact('invitation', 'recipient');
}

test('unlocked guests submit update and idempotently retry RSVP responses', function () {
    ['invitation' => $invitation, 'recipient' => $recipient] = authorizedUnlockedGuest($this);
    $first = [
        'respondent_name' => 'Avery Guest',
        'response' => RsvpResponse::Attending->value,
        'guest_count' => 2,
        'dietary_notes' => 'One vegan meal',
        'message' => 'We are delighted!',
    ];

    $this->putJson(route('guest.rsvp', $invitation), $first)->assertOk()->assertJsonPath('saved', true);
    $this->putJson(route('guest.rsvp', $invitation), $first)->assertOk();

    expect($recipient->rsvp()->count())->toBe(1)
        ->and($recipient->rsvp->revisions()->count())->toBe(1)
        ->and($invitation->events()->where('type', 'rsvp_submitted')->count())->toBe(1);

    $this->putJson(route('guest.rsvp', $invitation), [...$first, 'response' => RsvpResponse::Maybe->value, 'guest_count' => 1])->assertOk();

    expect($recipient->rsvp->refresh()->response)->toBe(RsvpResponse::Maybe)
        ->and($recipient->rsvp->revisions()->count())->toBe(2)
        ->and($invitation->events()->where('type', 'rsvp_updated')->count())->toBe(1);
});

test('sensitive RSVP text is encrypted at rest', function () {
    ['invitation' => $invitation, 'recipient' => $recipient] = authorizedUnlockedGuest($this);

    $this->putJson(route('guest.rsvp', $invitation), [
        'respondent_name' => 'Avery Guest',
        'response' => 'attending',
        'guest_count' => 1,
        'dietary_notes' => 'HIGHLY-SPECIFIC-DIETARY-NOTE',
        'message' => 'HIGHLY-SPECIFIC-PRIVATE-MESSAGE',
    ])->assertOk();

    $stored = DB::table('rsvps')->where('recipient_id', $recipient->id)->first();
    expect($stored->dietary_notes)->not->toContain('HIGHLY-SPECIFIC-DIETARY-NOTE')
        ->and($stored->message)->not->toContain('HIGHLY-SPECIFIC-PRIVATE-MESSAGE')
        ->and($recipient->rsvp->fresh()->dietary_notes)->toBe('HIGHLY-SPECIFIC-DIETARY-NOTE');
});

test('hosts can view complete recipient RSVP responses', function () {
    $host = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'team_id' => $host->current_team_id,
    ]);
    $recipient = InvitationRecipient::factory()->create([
        'invitation_id' => $invitation->id,
        'name' => 'Original Recipient',
    ]);
    $recipient->rsvp()->create([
        'respondent_name' => 'Avery Guest',
        'response' => RsvpResponse::Attending,
        'guest_count' => 2,
        'dietary_notes' => 'One vegan meal',
        'message' => 'We are delighted!',
        'submitted_at' => now(),
    ]);

    $this->actingAs($host)
        ->get(route('invitations.show', [$host->currentTeam, $invitation]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invitations/Show')
            ->where('invitation.recipients.0.rsvp.respondentName', 'Avery Guest')
            ->where('invitation.recipients.0.rsvp.response', 'attending')
            ->where('invitation.recipients.0.rsvp.responseLabel', 'Attending')
            ->where('invitation.recipients.0.rsvp.guestCount', 2)
            ->where('invitation.recipients.0.rsvp.dietaryNotes', 'One vegan meal')
            ->where('invitation.recipients.0.rsvp.message', 'We are delighted!'));

    $analyticsRecipient = app(InvitationAnalytics::class)->recipients($invitation)[0];

    expect($analyticsRecipient['rsvp'])
        ->toMatchArray([
            'respondentName' => 'Avery Guest',
            'response' => 'attending',
            'responseLabel' => 'Attending',
            'guestCount' => 2,
            'dietaryNotes' => 'One vegan meal',
            'message' => 'We are delighted!',
        ]);
});

test('RSVP enforces response guest limits and deadline', function () {
    ['invitation' => $invitation] = authorizedUnlockedGuest($this);
    $base = ['respondent_name' => 'Avery', 'response' => 'attending', 'guest_count' => 4];

    $this->putJson(route('guest.rsvp', $invitation), $base)->assertUnprocessable()->assertJsonValidationErrors('guest_count');
    $this->putJson(route('guest.rsvp', $invitation), [...$base, 'response' => 'not_attending', 'guest_count' => 1])->assertUnprocessable();
    $invitation->update(['rsvp_deadline_at' => now()->subMinute()]);
    $this->putJson(route('guest.rsvp', $invitation), [...$base, 'guest_count' => 1])->assertUnprocessable()->assertJsonValidationErrors('rsvp');
});

test('analytics excludes revoked recipients from conversion denominators', function () {
    $invitation = Invitation::factory()->published()->create();
    $active = InvitationRecipient::factory()->unlocked()->create(['invitation_id' => $invitation->id, 'revealed_at' => now()]);
    InvitationRecipient::factory()->unlocked()->revoked()->create(['invitation_id' => $invitation->id, 'revealed_at' => now()]);
    $active->rsvp()->create(['respondent_name' => 'Active', 'response' => RsvpResponse::Attending, 'guest_count' => 1, 'submitted_at' => now()]);

    $summary = app(InvitationAnalytics::class)->summary($invitation);

    expect($summary['total'])->toBe(1)
        ->and($summary['opened'])->toBe(1)
        ->and($summary['completed'])->toBe(1)
        ->and($summary['attending'])->toBe(1)
        ->and($summary['revoked'])->toBe(1)
        ->and($summary['completionRate'])->toBe(100.0)
        ->and($summary['rsvpRate'])->toBe(100.0);
});

test('calendar and outbound actions require unlock and record safe events', function () {
    ['invitation' => $invitation] = authorizedUnlockedGuest($this);

    $this->get(route('guest.calendar', $invitation))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertSee('BEGIN:VCALENDAR')
        ->assertSee('PRODID:-//SecretDuck//Invitation//EN')
        ->assertSee('UID:'.$invitation->public_id.'@secretduck');
    $this->get(route('guest.map', $invitation))->assertRedirect('https://maps.example.test/place');
    $this->get(route('guest.website', $invitation))->assertRedirect('https://event.example.test');

    expect($invitation->events()->whereIn('type', ['calendar_opened', 'map_opened', 'website_opened'])->count())->toBe(3);
});

test('host analytics are unavailable to unrelated users', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $invitation = Invitation::factory()->create(['team_id' => $owner->current_team_id]);

    $this->actingAs($other)
        ->get(route('invitations.analytics', [$other->currentTeam, $invitation]))
        ->assertNotFound();
});
