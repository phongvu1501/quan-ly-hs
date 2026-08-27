<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function __construct(private readonly JwtService $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?: $request->cookie($this->jwt->cookieName());
        $user = $this->jwt->userFromToken($token);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'JWT không hợp lệ hoặc hết hạn!',
            ], 401);
        }

        Auth::guard('web')->setUser($user);
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
