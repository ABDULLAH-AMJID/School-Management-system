<?php

use App\Http\Middleware\AuditMutations;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsurePermission;
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
        // Only opt in when Caddy runs on the same host and the backend is loopback-only.
        if (env('TRUST_LOCAL_HTTPS_PROXY', false)) {
            $middleware->trustProxies(at: ['127.0.0.1', '::1']);
        }

        $middleware->alias([
            'permission' => EnsurePermission::class,
            'audit' => AuditMutations::class,
            'active' => EnsureActiveUser::class,
            'password.changed' => EnsurePasswordChanged::class,
        ]);
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
