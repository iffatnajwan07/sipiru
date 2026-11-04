<?php

namespace App\Services;

use App\Auth\Contracts\AuthenticationProvider;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/** Login/logout + pencatatan login log & activity log. */
class AuthSessionService
{
    public function __construct(private ActivityLogger $logger) {}

    public function login(Request $request, User $user, string $method, bool $remember = false): void
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        $log = LoginLog::create([
            'user_id' => $user->id,
            'method' => $method,
            'login_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);
        $request->session()->put('login_log_id', $log->id);

        $this->logger->log('LOGIN', $user, "Login ({$method})", actor: $user);
    }

    public function logout(Request $request): void
    {
        $user = $request->user();

        if ($user) {
            if ($id = $request->session()->get('login_log_id')) {
                LoginLog::whereKey($id)->where('user_id', $user->id)->whereNull('logout_at')->update(['logout_at' => now()]);
            }
            $this->logger->log('LOGOUT', $user, 'Logout', actor: $user);

            if ($user->isPegawai()) {
                app(AuthenticationProvider::class)->logout();
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
