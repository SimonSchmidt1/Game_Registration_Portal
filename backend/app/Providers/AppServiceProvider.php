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
        return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
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
