<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureFinanceSchema
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Schema::hasTable('fee_assessments') || ! Schema::hasTable('payrolls')) {
            return redirect()
                ->route('admin.school.dashboard')
                ->with('warning', 'Module tài chính đang chờ hoàn tất migration an toàn trên database development.');
        }

        return $next($request);
    }
}
