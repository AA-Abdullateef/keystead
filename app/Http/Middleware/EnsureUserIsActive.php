<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** A suspended account is cut off immediately, even if it still holds a valid token. */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'This account is suspended.'], 403);
            }
            auth()->logout();
            abort(403, 'This account is suspended.');
        }

        return $next($request);
    }
}
