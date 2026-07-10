<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * SetBearerTokenFromCookie
 *
 * Reads the `auth_token` httpOnly cookie and injects it into the request
 * as a bearer token so that Sanctum's standard `auth:sanctum` middleware
 * can authenticate the request without any other changes.
 *
 * Flow:
 *   Browser → [httpOnly cookie: auth_token=<token>]
 *          → This middleware reads the cookie and sets Authorization header
 *          → Sanctum reads the Authorization header and authenticates
 *
 * This allows the API to support BOTH:
 *   1. Cookie-based auth (browsers with httpOnly cookie)
 *   2. Bearer token auth (mobile apps / Postman / other clients)
 */
class SetBearerTokenFromCookie
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Only inject if no Authorization header is already present
        // (preserves backward-compatibility with bearer token clients)
        if (!$request->bearerToken()) {
            $token = $request->cookie('auth_token');

            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        return $next($request);
    }
}
