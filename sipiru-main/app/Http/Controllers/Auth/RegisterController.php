<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\AuthSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, AuthSessionService $sessions, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validated();

        // KTP disimpan di disk PRIVATE (storage/app/private) — tidak dapat diakses lewat URL publik.
        $path = $request->file('ktp')->store('ktp', 'local');

        try {
            $user = DB::transaction(function () use ($data, $path, $logger) {
                $user = new User([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'nik' => $data['nik'],
                    'password' => $data['password'],
                ]);
                $user->user_type = UserType::Eksternal;
                $user->ktp_path = $path;
                $user->is_active = true;
                $user->policy_accepted_at = now();
                $user->policy_version = config('booking.policy_version');
                $user->save();

                $logger->log('REGISTER', $user, 'Registrasi pengguna eksternal', actor: $user);

                return $user;
            });
        } catch (Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        $sessions->login($request, $user, 'eksternal');

        return redirect()->route('dashboard')->with('success', 'Registrasi berhasil. Selamat datang!');
    }
}
