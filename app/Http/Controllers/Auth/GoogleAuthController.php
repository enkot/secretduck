<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\FindOrCreateGoogleUser;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    public function __construct(private FindOrCreateGoogleUser $findOrCreateGoogleUser) {}

    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return $this->failed(__('Google sign-in was cancelled.'));
        }

        try {
            $googleUser = Socialite::driver('google')->user();

            if (! $googleUser instanceof GoogleUser) {
                return $this->failed(__('Google returned an invalid account response.'));
            }

            $user = $this->findOrCreateGoogleUser->handle($googleUser);
        } catch (InvalidStateException) {
            return $this->failed(__('Your Google sign-in session expired. Please try again.'));
        } catch (ValidationException $exception) {
            return to_route('login')->withErrors($exception->errors());
        } catch (Throwable $exception) {
            report($exception);

            return $this->failed(__("We couldn't sign you in with Google. Please try again."));
        }

        if ($user->wasRecentlyCreated) {
            event(new Registered($user));
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => false,
            ]);

            TwoFactorAuthenticationChallenged::dispatch($user);

            return to_route('two-factor.login');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('invitations.index', [
            'current_team' => $user->currentTeam,
        ]));
    }

    private function failed(string $message): RedirectResponse
    {
        return to_route('login')->withErrors(['google' => $message]);
    }
}
