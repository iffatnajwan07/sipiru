<?php

namespace App\Providers;

use App\Auth\Contracts\AuthenticationProvider;
use App\Auth\Providers\LocalDevelopmentAuthProvider;
use App\Models\Setting;
use App\Policies\EmailSettingPolicy;
use App\Services\MailSettings;
use App\Services\SettingRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingRepository::class);

        // Provider autentikasi internal dipilih lewat AUTH_INTERNAL_PROVIDER (local|simpeg)
        $this->app->bind(AuthenticationProvider::class, function ($app) {
            $key = config('auth_providers.internal', 'local');
            $class = config("auth_providers.providers.$key", LocalDevelopmentAuthProvider::class);

            return $app->make($class);
        });
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        Paginator::defaultView('components.pagination');

        // SMTP dari database (jika dipilih admin) diterapkan tepat sebelum mailer dibuat
        $this->app->afterResolving('mail.manager', function () {
            $this->app->make(MailSettings::class)->apply();
        });

        Gate::policy(Setting::class, EmailSettingPolicy::class);

        Password::defaults(fn () => Password::min(8)->letters()->mixedCase()->numbers());

        RateLimiter::for('login', function (Request $request) {
            $key = strtolower((string) ($request->input('email') ?? $request->input('nip') ?? $request->input('login')));

            return [
                Limit::perMinute(5)->by($key.'|'.$request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });
        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by($request->ip()));
        RateLimiter::for('ajax', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('verify', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('smtp-test', fn (Request $request) => Limit::perMinute(3)->by($request->user()?->id ?: $request->ip()));
    }
}
