<?php

namespace App\Http\Middleware;

use App\Services\PortalAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBackOfficeAccess
{
    public function __construct(private readonly PortalAccessService $portalAccess) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user() && $this->portalAccess->canAccessBackOffice($request->user()),
            403,
            'Tài khoản cổng học sinh hoặc giáo viên không được truy cập khu vực quản trị.'
        );

        return $next($request);
    }
}
