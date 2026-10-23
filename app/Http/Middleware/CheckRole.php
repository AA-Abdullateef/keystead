<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Usage: ->middleware('role:admin,manager'). Role names come from the roles table. */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (! $user || ! $user->isActive() || ! $user->hasAnyRole($roles)) {
            abort(403, 'You are not authorised to access this area.');
        }

        return $next($request);
    }
}
