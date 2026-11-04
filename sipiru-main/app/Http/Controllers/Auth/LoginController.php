<?php

namespace App\Http\Controllers\Auth;

use App\Auth\Exceptions\AuthProviderException;
use App\Auth\InternalAuthenticator;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private AuthSessionService $sessions) {}

    public function show(): View
    {
        return view('auth.login', [
            'providerName' => config('auth_providers.internal'),
        ]);
    }

    /** Pegawai USK — melalui AuthenticationProvider (local / SIMPEG). */
    public function internal(Request $request, InternalAuthenticator $authenticator): RedirectResponse
    {
        $data = $request->validate([
            'nip' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'max:255'],
        ], [], ['nip' => 'NIP']);

        try {
            $user = $authenticator->attempt(trim($data['nip']), $data['password']);
        } catch (AuthProviderException $e) {
            report($e);
            throw ValidationException::withMessages(['nip' => $e->getMessage()])->redirectTo(route('login').'#pegawai');
        }

        if (! $user) {
            throw ValidationException::withMessages(['nip' => 'NIP atau password salah.'])->redirectTo(route('login').'#pegawai');
        }
        $this->ensureActive($user, 'nip');

        $this->sessions->login($request, $user, 'simpeg:'.$authenticator->provider()->name());

        return redirect()->intended(route('dashboard'));
    }

    /** Pengguna eksternal — email + password lokal. */
    public function external(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('email', strtolower($data['email']))
            ->where('user_type', UserType::Eksternal->value)
            ->first();

        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Email atau password salah.'])->redirectTo(route('login').'#eksternal');
        }
        $this->ensureActive($user, 'email');

        $this->sessions->login($request, $user, 'eksternal', $request->boolean('remember'));

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $isAdmin = $request->user()?->isAdmin();
        $this->sessions->logout($request);

        return redirect()->route($isAdmin ? 'admin.login' : 'login')->with('status', 'Anda telah keluar.');
    }

    private function ensureActive(User $user, string $field): void
    {
        if (! $user->is_active) {
            throw ValidationException::withMessages([$field => 'Akun Anda dinonaktifkan. Hubungi admin.']);
        }
    }
}
