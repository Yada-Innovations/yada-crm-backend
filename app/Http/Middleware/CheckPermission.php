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
            return response()->json([
                'message'    => 'Forbidden. Missing permission: ' . $permission,
                'permission' => $permission,
            ], 403);
        }

        return $next($request);
    }
}