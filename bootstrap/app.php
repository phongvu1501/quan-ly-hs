<?php

use App\Http\Middleware\AuthenticateJwt;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureBackOfficeAccess;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\ResolveJwtUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: [env('JWT_COOKIE', 'school_access_token')]);
        $middleware->web(
            prepend: [ResolveJwtUser::class],
            append: [EnsurePasswordChanged::class]
        );
        $middleware->alias([
            'backoffice' => EnsureBackOfficeAccess::class,
            'permission' => CheckPermission::class,
            'jwt.auth' => AuthenticateJwt::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request): string {
            $request->session()->flash('error', 'Vui lòng đăng nhập.');

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
