<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Contoh: role:admin atau role:pegawai,eksternal */
class EnsureUserType
{
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->user_type->value, $types, true)) {
            // Admin yang membuka area user diarahkan ke panel admin
            if ($user?->isAdmin() && ! $request->expectsJson()) {
                return redirect()->route('admin.dashboard');
            }
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
