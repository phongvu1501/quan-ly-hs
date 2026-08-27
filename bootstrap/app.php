<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\AuthenticateJwt;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\ResolveJwtUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: [env('JWT_COOKIE', 'school_access_token')]);
        $middleware->web(append: [ResolveJwtUser::class, EnsurePasswordChanged::class]);
        $middleware->alias([
            'permission' => CheckPermission::class,
            'jwt.auth' => AuthenticateJwt::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
