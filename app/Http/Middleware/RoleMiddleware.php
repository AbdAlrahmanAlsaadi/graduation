<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً',
            ], 401);
        }

        if (! $user->hasRole($role)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول',
                'required_role' => $role,
                'your_role' => $user->getRoleNames()->values(),
            ], 403);
        }

        return $next($request);
    }
}
