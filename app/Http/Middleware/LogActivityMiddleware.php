<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogActivityMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (Auth::check()) {

            ActivityLog::query()->create([

                'user_id' => Auth::id(),

                'action' => $this->resolveAction($request),

                'method' => $request->method(),

                'endpoint' => $request->path(),

                'description' => $this->resolveDescription($request),

                'ip_address' => $request->ip(),
            ]);
        }

        return $response;
    }

    private function resolveAction(Request $request): string
    {
        return match ($request->method()) {

            'POST' => 'Create',

            'PUT', 'PATCH' => 'Update',

            'DELETE' => 'Delete',

            default => 'View',
        };
    }

    private function resolveDescription(Request $request): string
    {
        return $request->method() . ' request to ' . $request->path();
    }
}
