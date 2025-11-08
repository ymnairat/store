<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission = null)
    {
        // استثناء الصفحات العامة
        $except = [
            'login',
            'register',
            'password/*',
            'forgot-password',
            'reset-password/*',
        ];

        foreach ($except as $uri) {
            if ($request->is($uri)) {
                return $next($request);
            }
        }

        // تحقق من الصلاحية إذا تم تمرير permission
        if ($permission && !$request->user()->hasPermission($permission)) {
            return redirect()->route('dashboard')->with('error', 'ليس لديك صلاحية للوصول');
        }

        return $next($request);
    }
}
