<?php

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;

test('users can be redirected to Google', function () {
    Socialite::fake('google');

    $response = $this->get(route('auth.google.redirect'));

    $response->assertRedirect('https://socialite.fake/google/authorize');
});

test('new users can authenticate with a verified Google account', function () {
    Socialite::fake('google', googleUser());

    $response = $this->get(route('auth.google.callback'));

    $user = User::query()->where('email', 'google@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('invitations.index', [
        'current_team' => $user->currentTeam,
    ]));

    expect($user->google_id)->toBe('google-123')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->personalTeam())->not->toBeNull();
});

test('Google authentication links an existing account by verified email', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'google@example.com',
    ]);
    Socialite::fake('google', googleUser());

    $this->get(route('auth.google.callback'));

    $user->refresh();

    $this->assertAuthenticatedAs($user);

    expect(User::query()->where('email', 'google@example.com')->count())->toBe(1)
        ->and($user->google_id)->toBe('google-123')
        ->and($user->email_verified_at)->not->toBeNull();
});

test('Google authentication preserves two factor protection', function () {
    $user = User::factory()->withTwoFactor()->create([
        'email' => 'google@example.com',
    ]);
    Event::fake([TwoFactorAuthenticationChallenged::class]);
    Socialite::fake('google', googleUser());

    $response = $this->get(route('auth.google.callback'));

    $this->assertGuest();
    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    Event::assertDispatched(TwoFactorAuthenticationChallenged::class, fn ($event): bool => $event->user->is($user));
});

test('Google authentication requires a verified email address', function () {
    Socialite::fake('google', googleUser(emailVerified: false));

    $response = $this->get(route('auth.google.callback'));

    $this->assertGuest();
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'google' => 'Google did not provide a verified email address.',
        ]);

    expect(User::query()->where('email', 'google@example.com')->exists())->toBeFalse();
});

test('Google authentication does not overwrite a different linked account', function () {
    $user = User::factory()->create([
        'email' => 'google@example.com',
    ]);
    $user->forceFill(['google_id' => 'different-google-id'])->save();
    Socialite::fake('google', googleUser());

    $response = $this->get(route('auth.google.callback'));

    $this->assertGuest();
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'google' => 'This email is already linked to another Google account.',
        ]);

    expect($user->fresh()->google_id)->toBe('different-google-id');
});

test('cancelled Google authentication returns to login with an error', function () {
    $response = $this->get(route('auth.google.callback', [
        'error' => 'access_denied',
    ]));

    $this->assertGuest();
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'google' => 'Google sign-in was cancelled.',
        ]);
});

function googleUser(bool $emailVerified = true): GoogleUser
{
    $attributes = [
        'id' => 'google-123',
        'name' => 'Google User',
        'email' => 'google@example.com',
        'email_verified' => $emailVerified,
    ];

    return (new GoogleUser)->setRaw($attributes)->map($attributes);
}
