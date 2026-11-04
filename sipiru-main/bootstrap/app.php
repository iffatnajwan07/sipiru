<?php

use App\Http\Middleware\EnsurePolicyAccepted;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserType;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserType::class,
            'active' => EnsureUserIsActive::class,
            'policy.accepted' => EnsurePolicyAccepted::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*')
            ? route('admin.login')
            : route('login'));

        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->isAdmin()
            ? route('admin.dashboard')
            : route('dashboard'));

        // Nginx berada di depan PHP-FPM (reverse proxy)
        $middleware->trustProxies(at: ['127.0.0.1', '172.16.0.0/12', '10.0.0.0/8', '192.168.0.0/16']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        // Jangan pernah menulis password/secret ke log
        $exceptions->dontFlash(['password', 'password_confirmation', 'current_password', 'smtp_password']);
    })->create();
