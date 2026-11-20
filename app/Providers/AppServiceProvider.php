<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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
        Password::defaults(fn (): Password => Password::min(8));

        ResetPassword::createUrlUsing(
            fn (object $notifiable, string $token): string => config('app.url').'/reset-password/'.$token.'?email='.urlencode($notifiable->getEmailForPasswordReset()),
        );

        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by(
            Str::transliterate(Str::lower($request->string('email')->toString()).'|'.$request->ip())
        ));

        RateLimiter::for('password-reset', fn (Request $request): Limit => Limit::perMinute(5)->by(
            Str::transliterate(Str::lower($request->string('email')->toString()).'|'.$request->ip())
        ));
    }
}
