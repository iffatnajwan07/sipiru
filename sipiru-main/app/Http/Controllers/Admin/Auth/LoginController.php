<?php

namespace App\Http\Controllers\Admin\Auth;

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
    public function show(): View
    {
        return view('admin.auth.login');
    }

    /** Login admin menggunakan email atau NIP + password lokal. */
    public function store(Request $request, AuthSessionService $sessions): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:255'],
        ], [], ['login' => 'email / NIP']);

        $login = trim($data['login']);
        $user = User::where('user_type', UserType::Admin->value)
            ->where(fn ($q) => $q->where('email', strtolower($login))->orWhere('nip', $login))
            ->first();

        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => 'Kredensial admin tidak valid.']);
        }
        if (! $user->is_active) {
            throw ValidationException::withMessages(['login' => 'Akun admin dinonaktifkan.']);
        }

        $sessions->login($request, $user, 'admin', $request->boolean('remember'));

        return redirect()->intended(route('admin.dashboard'));
    }
}
