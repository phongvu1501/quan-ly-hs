<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveJwtUser
{
    public function __construct(private readonly JwtService $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            $token = $request->bearerToken() ?: $request->cookie($this->jwt->cookieName());
            if ($user = $this->jwt->userFromToken($token)) {
                Auth::guard('web')->setUser($user);
                $request->setUserResolver(fn () => $user);
            }
        }

        return $next($request);
    }
}
