<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (! $user->is_active || $user->trashed())) {
            $isAdmin = $user->isAdmin();
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route($isAdmin ? 'admin.login' : 'login')
                ->withErrors(['login' => 'Akun Anda dinonaktifkan. Hubungi admin.']);
        }

        return $next($request);
    }
}
