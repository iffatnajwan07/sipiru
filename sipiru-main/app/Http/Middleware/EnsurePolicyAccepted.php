<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** User (non-admin) wajib menyetujui kebijakan peminjaman versi terbaru. */
class EnsurePolicyAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isAdmin() && ! $user->hasAcceptedCurrentPolicy()) {
            if ($request->expectsJson()) {
                abort(403, 'Silakan setujui kebijakan peminjaman terlebih dahulu.');
            }
            session()->put('url.intended', $request->fullUrl());

            return redirect()->route('policy.show');
        }

        return $next($request);
    }
}
