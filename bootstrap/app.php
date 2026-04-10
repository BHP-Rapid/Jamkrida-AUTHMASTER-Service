<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AuthenticateJwt;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureMitra;
use App\Http\Middleware\EnsureForwardedUserToken;
use App\Http\Middleware\EnsureInternalServiceToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ensure.mitra' => EnsureMitra::class,
            'jwt.auth' => AuthenticateJwt::class,
            'check.permission' => CheckPermission::class,
            'check.role' => CheckRole::class,
            'internal.service' => EnsureInternalServiceToken::class,
            'internal.user' => EnsureForwardedUserToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
