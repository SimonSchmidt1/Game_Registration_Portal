<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
    // Reset password URL
    ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
        return config('app.frontend_url')."/reset-password?token=$token&email={$notifiable->getEmailForPasswordReset()}";
    });

    // ✅ Rate limiting pre login
    RateLimiter::for('login', function (Request $request) {
        $email = (string) $request->email;
        return Limit::perMinute(6)->by($email.$request->ip());
    });

    // ✅ Rate limiting pre registráciu
    RateLimiter::for('register', function (Request $request) {
        $email = (string) $request->email;
        return Limit::perMinute(3)->by($email.$request->ip());
    });

    // ✅ Rate limiting pre vytváranie projektov
    RateLimiter::for('projects', function (Request $request) {
        return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
    });

    // ✅ Rate limiting pre hodnotenie projektov
    RateLimiter::for('ratings', function (Request $request) {
        if ($request->user()) {
            return Limit::perMinute(60)->by('rating:user:' . $request->user()->id);
        }

        $guestId = (string) ($request->cookie('guest_rating_id') ?? '');
        $projectId = (string) ($request->route('id') ?? 'unknown');
        $uaHash = substr(sha1((string) ($request->userAgent() ?? 'unknown')), 0, 16);
        $key = $guestId !== ''
            ? 'rating:guest:' . $projectId . ':' . $guestId
            : 'rating:fallback:' . $projectId . ':' . $request->ip() . ':' . $uaHash;

        return Limit::perMinute(20)->by($key);
    });

    // ✅ Rate limiting pre zobrazenia projektov
    // Auth users: no throttle — each visit counts
    // Guests: limit per project by guest cookie (fallback to IP+UA)
    RateLimiter::for('views', function (Request $request) {
        if ($request->user()) {
            return Limit::none();
        }

        $projectId = $request->route('id') ?? 'unknown';
        $guestId = (string) ($request->cookie('guest_rating_id') ?? '');
        $uaHash = substr(sha1((string) ($request->userAgent() ?? 'unknown')), 0, 16);
        $key = $guestId !== ''
            ? 'view:guest:' . $projectId . ':' . $guestId
            : 'view:fallback:' . $projectId . ':' . $request->ip() . ':' . $uaHash;

        return Limit::perMinute(6)->by($key);
    });

    // ✅ Rate limiting pre resend verification email (1/min, 5/hour per email+IP)
    RateLimiter::for('resend-verification', function (Request $request) {
        $email = Str::lower((string) $request->email);
        return [
            Limit::perMinute(1)->by($email . $request->ip()),
            Limit::perHour(5)->by($email . $request->ip()),
        ];
    });

    // ✅ Rate limiting pre zabudnuté heslo / resend reset email
    RateLimiter::for('forgot-password', function (Request $request) {
        $email = Str::lower((string) $request->email);
        // Limit: 1 request per minute per email+IP, with a secondary hourly cap
        return [
            Limit::perMinute(1)->by($email.$request->ip()),
            Limit::perHour(5)->by($email.$request->ip()),
        ];
    });
}
}
