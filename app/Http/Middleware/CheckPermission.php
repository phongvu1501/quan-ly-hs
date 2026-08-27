<?php

namespace App\Http\Middleware;

use App\Models\Permission;
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
        $requiredPermissions = [$permission];
        [$resource, $action] = array_pad(explode('.', $permission, 2), 2, null);
        $managePermission = "{$resource}.manage";

        if (
            in_array($action, ['create', 'edit', 'delete'], true)
            && Permission::where('slug', $managePermission)->exists()
        ) {
            $requiredPermissions[] = $managePermission;
        }

        if (collect($requiredPermissions)->contains(
            fn(string $requiredPermission) => ! auth()->user()->hasPermission($requiredPermission)
        )) {

            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        return $next($request);
    }
}
