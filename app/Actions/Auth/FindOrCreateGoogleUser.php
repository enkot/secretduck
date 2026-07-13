<?php

namespace App\Actions\Auth;

use App\Actions\Teams\CreateTeam;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Two\User as GoogleUser;

class FindOrCreateGoogleUser
{
    public function __construct(private CreateTeam $createTeam) {}

    public function handle(GoogleUser $googleUser): User
    {
        $googleId = Str::of((string) $googleUser->getId())->trim()->value();
        $email = Str::of((string) $googleUser->getEmail())->trim()->lower()->value();

        if ($googleId === '') {
            throw ValidationException::withMessages([
                'google' => __('Google did not provide an account identifier.'),
            ]);
        }

        if (! $this->hasVerifiedEmail($googleUser, $email)) {
            throw ValidationException::withMessages([
                'google' => __('Google did not provide a verified email address.'),
            ]);
        }

        return DB::transaction(function () use ($googleUser, $googleId, $email): User {
            $user = User::query()
                ->where('google_id', $googleId)
                ->lockForUpdate()
                ->first();

            if ($user) {
                return $user;
            }

            $user = User::query()
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if ($user) {
                if ($user->google_id !== null && $user->google_id !== $googleId) {
                    throw ValidationException::withMessages([
                        'google' => __('This email is already linked to another Google account.'),
                    ]);
                }

                $user->forceFill([
                    'google_id' => $googleId,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();

                return $user;
            }

            $name = Str::of((string) $googleUser->getName())->squish()->value();
            $name = $name !== '' ? $name : Str::before($email, '@');

            $user = new User([
                'name' => Str::limit($name, 255, ''),
                'email' => $email,
                'password' => Str::random(64),
            ]);
            $user->google_id = $googleId;
            $user->email_verified_at = Carbon::now();
            $user->save();

            $this->createTeam->handle($user, $user->name."'s Team", isPersonal: true);

            return $user;
        });
    }

    private function hasVerifiedEmail(GoogleUser $googleUser, string $email): bool
    {
        $rawUser = $googleUser->getRaw();
        $isVerified = $rawUser['email_verified'] ?? $rawUser['verified_email'] ?? false;

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && $isVerified === true;
    }
}
