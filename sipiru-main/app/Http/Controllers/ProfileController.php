<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.show', ['user' => $request->user()]);
    }

    public function update(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $request->merge(['phone' => preg_replace('/[\s\-()]/', '', (string) $request->input('phone'))]);

        $rules = ['phone' => ['required', 'regex:/^(\+62|62|0)8[1-9][0-9]{6,11}$/']];
        // Data pegawai (nama, email) bersumber dari SIMPEG
        if ($user->isEksternal()) {
            $rules['name'] = ['required', 'string', 'min:3', 'max:150'];
        }
        $data = $request->validate($rules, ['phone.regex' => 'Nomor HP tidak valid.']);

        $before = $user->only(array_keys($data));
        $user->fill($data)->save();
        $logger->logChanges('UPDATE_PROFILE', $user, $before, $user->only(array_keys($data)));

        return back()->with('success', 'Profil diperbarui.');
    }

    public function password(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isEksternal(), 403, 'Password pegawai dikelola oleh SIMPEG.');

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->forceFill(['password' => $request->input('password')])->save();
        $logger->log('CHANGE_PASSWORD', $user, 'Mengubah password');

        return back()->with('success', 'Password berhasil diubah.');
    }

    public function ktp(Request $request): StreamedResponse
    {
        $user = $request->user();
        $this->authorize('viewKtp', $user);
        abort_unless(Storage::disk('local')->exists($user->ktp_path), 404);

        return Storage::disk('local')->response($user->ktp_path, 'ktp-'.$user->id.'.'.pathinfo($user->ktp_path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
