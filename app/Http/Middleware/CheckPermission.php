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

        if (!$user->hasPermissionTo($permission)) {
            // Do NOT leak which permission is missing
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}