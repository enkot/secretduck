<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('guest-authorize', fn (Request $request): array => [
            Limit::perMinute(10)->by($request->ip().'|'.(string) $request->route('invitation')),
            Limit::perHour(60)->by($request->ip()),
        ]);
        RateLimiter::for('guest-session', fn (Request $request) => Limit::perMinute(60)->by($this->guestRateLimitKey($request)));
        RateLimiter::for('challenge-submit', fn (Request $request) => Limit::perMinute(10)->by($this->guestRateLimitKey($request)));
        RateLimiter::for('guest-rsvp', fn (Request $request) => Limit::perMinute(10)->by($this->guestRateLimitKey($request)));
        RateLimiter::for('host-token', fn (Request $request) => Limit::perMinute(30)->by((string) $request->user()?->id));
    }

    private function guestRateLimitKey(Request $request): string
    {
        $cookieName = config('questinvite.guest_cookie');
        $browserToken = is_string($cookieName) ? $request->cookie($cookieName) : null;

        return $request->ip().'|'.(is_string($browserToken) ? hash('sha256', $browserToken) : 'anonymous');
    }
}
