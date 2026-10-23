<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opt-in idempotency for money-moving POSTs. A client sends `Idempotency-Key: <unique string>`.
 *
 *  first request          → runs normally; the response is stored against (user, key)
 *  same key, same body    → the stored response is replayed (header `Idempotent-Replayed: true`), nothing runs twice
 *  same key, other body   → 422 (a key must never be reused for a different request)
 *  same key, still running→ 409 (retry shortly)
 *
 * Server errors (5xx) and throttling are not stored, so the client may retry them with the same key.
 */
class IdempotentRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');
        $user = $request->user();

        if ($key === null || $key === '' || ! $user) {
            return $next($request);
        }
        if (strlen($key) > 128) {
            return $this->fail('The Idempotency-Key header must be at most 128 characters.', 422);
        }

        $fingerprint = hash('sha256', $request->method() . '|' . $request->path() . '|' . $request->getContent());

        try {
            $record = IdempotencyKey::create(['user_id' => $user->id, 'key' => $key, 'fingerprint' => $fingerprint]);
        } catch (UniqueConstraintViolationException) {
            $existing = IdempotencyKey::where('user_id', $user->id)->where('key', $key)->first();

            if (! $existing) {
                return $this->fail('Please retry the request.', 409);
            }
            if (! hash_equals($existing->fingerprint, $fingerprint)) {
                return $this->fail('This Idempotency-Key was already used with a different request.', 422);
            }
            if ($existing->status_code === null) {
                return $this->fail('A request with this Idempotency-Key is still being processed.', 409);
            }

            return response($existing->response_body, $existing->status_code, ['Content-Type' => 'application/json', 'Idempotent-Replayed' => 'true']);
        }

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $record->delete(); // nothing was stored: allow a retry with the same key
            throw $e;
        }

        if ($response->getStatusCode() >= 500 || $response->getStatusCode() === 429) {
            $record->delete();
        } else {
            $record->update(['status_code' => $response->getStatusCode(), 'response_body' => $response->getContent()]);
        }

        return $response;
    }

    private function fail(string $message, int $status): Response
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
