<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $permission
    ): Response {

        // Chưa đăng nhập
        if (!auth()->check()) {
            return redirect()
                ->route('login')
                ->with('error', 'Vui lòng đăng nhập.');
        }

        // Kiểm tra permission
        if (!auth()->user()->hasPermission($permission)) {

            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        return $next($request);
    }
}
