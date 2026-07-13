<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Guest\ChallengeController as GuestChallengeController;
use App\Http\Controllers\Guest\GuestAuthorizationController;
use App\Http\Controllers\Guest\InvitationController as GuestInvitationController;
use App\Http\Controllers\Guest\InvitationUtilityController;
use App\Http\Controllers\Guest\RevealController;
use App\Http\Controllers\Guest\RsvpController;
use App\Http\Controllers\Host\ChallengeController;
use App\Http\Controllers\Host\CoverImageController;
use App\Http\Controllers\Host\InvitationAnalyticsController;
use App\Http\Controllers\Host\InvitationController;
use App\Http\Controllers\Host\InvitationRecipientController;
use App\Http\Controllers\Host\InvitationStateController;
use App\Http\Controllers\Host\RecipientAccessController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::prefix('auth/google')
    ->name('auth.google.')
    ->middleware(['guest', 'throttle:10,1'])
    ->group(function () {
        Route::get('redirect', [GoogleAuthController::class, 'redirect'])->name('redirect');
        Route::get('callback', [GoogleAuthController::class, 'callback'])->name('callback');
    });

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('invitations/{invitation}/preview', [InvitationController::class, 'preview'])->name('invitations.preview');
        Route::get('invitations/{invitation}/analytics', InvitationAnalyticsController::class)->name('invitations.analytics');
        Route::post('invitations/{invitation}/publish', [InvitationStateController::class, 'publish'])->name('invitations.publish');
        Route::post('invitations/{invitation}/pause', [InvitationStateController::class, 'pause'])->name('invitations.pause');
        Route::post('invitations/{invitation}/resume', [InvitationStateController::class, 'resume'])->name('invitations.resume');
        Route::post('invitations/{invitation}/archive', [InvitationStateController::class, 'archive'])->name('invitations.archive');
        Route::put('invitations/{invitation}/challenge', [ChallengeController::class, 'update'])->name('invitations.challenge.update');
        Route::post('invitations/{invitation}/cover', [CoverImageController::class, 'store'])->name('invitations.cover.store');
        Route::delete('invitations/{invitation}/cover', [CoverImageController::class, 'destroy'])->name('invitations.cover.destroy');
        Route::post('invitations/{invitation}/recipients', [InvitationRecipientController::class, 'store'])->name('invitations.recipients.store');
        Route::patch('invitations/{invitation}/recipients/{recipient}', [InvitationRecipientController::class, 'update'])->name('invitations.recipients.update');
        Route::middleware('throttle:host-token')->group(function () {
            Route::post('invitations/{invitation}/recipients/{recipient}/link', [RecipientAccessController::class, 'link'])->name('invitations.recipients.link');
            Route::post('invitations/{invitation}/recipients/{recipient}/regenerate-link', [RecipientAccessController::class, 'regenerate'])->name('invitations.recipients.regenerate');
            Route::post('invitations/{invitation}/recipients/{recipient}/revoke', [RecipientAccessController::class, 'revoke'])->name('invitations.recipients.revoke');
            Route::post('invitations/{invitation}/recipients/{recipient}/reactivate', [RecipientAccessController::class, 'reactivate'])->name('invitations.recipients.reactivate');
        });
        Route::resource('invitations', InvitationController::class)->except(['destroy']);
    });

Route::middleware(['auth'])->group(function () {
    Route::get('team-invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('team_invitations.accept');
    Route::delete('team-invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('team_invitations.decline');
});

Route::prefix('open/{invitation:public_id}')->group(function () {
    Route::get('/', GuestInvitationController::class)->name('guest.show');
    Route::post('authorize', GuestAuthorizationController::class)->middleware('throttle:guest-authorize')->name('guest.authorize');
    Route::middleware('throttle:guest-session')->group(function () {
        Route::post('challenge/start', [GuestChallengeController::class, 'start'])->name('guest.challenge.start');
        Route::post('challenge', [GuestChallengeController::class, 'submit'])->middleware('throttle:challenge-submit')->name('guest.challenge.submit');
        Route::post('challenge/hint', [GuestChallengeController::class, 'hint'])->middleware('throttle:challenge-submit')->name('guest.challenge.hint');
        Route::get('reveal', RevealController::class)->name('guest.reveal');
        Route::put('rsvp', RsvpController::class)->middleware('throttle:guest-rsvp')->name('guest.rsvp');
        Route::get('calendar.ics', [InvitationUtilityController::class, 'calendar'])->name('guest.calendar');
        Route::get('map', [InvitationUtilityController::class, 'map'])->name('guest.map');
        Route::get('website', [InvitationUtilityController::class, 'website'])->name('guest.website');
    });
});

require __DIR__.'/settings.php';
