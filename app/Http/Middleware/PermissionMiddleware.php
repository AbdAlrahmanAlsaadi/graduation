<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً',
            ], 401);
        }

        if (! $user->can($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك الصلاحية الكافية',
                'required_permission' => $permission,
                'your_permissions' => $user->getAllPermissions()->pluck('name')->values(),
            ], 403);
        }

        return $next($request);
    }
}
