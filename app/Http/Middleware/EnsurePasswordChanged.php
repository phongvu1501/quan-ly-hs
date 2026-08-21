<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password && !$request->routeIs('profile.*', 'logout')) {
            return redirect()->route('profile.show')
                ->with('warning', 'Bạn phải đổi mật khẩu mặc định trước khi sử dụng hệ thống.');
        }

        return $next($request);
    }
}
