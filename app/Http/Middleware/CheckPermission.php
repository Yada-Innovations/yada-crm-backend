<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $hasPermission = $user->roles()
            ->whereHas('permissions', function ($q) use ($permission) {
                $q->where('name', $permission);
            })->exists();

        if (!$hasPermission) {
            return response()->json([
                'message'    => 'Forbidden. Missing permission: ' . $permission,
            ], 403);
        }

        return $next($request);
    }
}