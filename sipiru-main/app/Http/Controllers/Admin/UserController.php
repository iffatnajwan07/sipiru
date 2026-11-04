<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);
        $request->validate(['type' => ['nullable', Rule::enum(UserType::class)]]);

        $users = User::query()
            ->withCount('bookings')
            ->when($request->q, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)
                    ->orWhere('nip', 'like', $like)->orWhere('nik', 'like', $like));
            })
            ->when($request->type, fn ($q, $t) => $q->where('user_type', $t))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        return view('admin.users.show', [
            'user' => $user,
            'bookings' => $user->bookings()->with('room.building')->latest('start_at')->limit(10)->get(),
            'logins' => $user->loginLogs()->latest('login_at')->limit(10)->get(),
        ]);
    }

    public function toggle(User $user, ActivityLogger $logger): RedirectResponse
    {
        $this->authorize('toggleActive', $user);
        $user->forceFill(['is_active' => ! $user->is_active])->save();
        $logger->log($user->is_active ? 'ACTIVATE_USER' : 'DEACTIVATE_USER', $user, ($user->is_active ? 'Mengaktifkan' : 'Menonaktifkan')." akun {$user->email}");

        return back()->with('success', $user->is_active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.');
    }

    public function ktp(User $user, ActivityLogger $logger): StreamedResponse
    {
        $this->authorize('viewKtp', $user);
        abort_unless(Storage::disk('local')->exists($user->ktp_path), 404);
        $logger->log('VIEW_KTP', $user, "Melihat KTP {$user->email}");

        return Storage::disk('local')->response($user->ktp_path, 'ktp-'.$user->id.'.'.pathinfo($user->ktp_path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
