<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Gate for money-moving API actions: the account's e-mail address must be confirmed. */
class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (config('keystead.require_verified_email_for_money') && $user && ! $user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Confirm your email address before moving money. Use POST /api/v1/auth/resend-verification if you need a new link.',
            ], 403);
        }

        return $next($request);
    }
}
