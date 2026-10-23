<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Registration (e-mail verification) and password-reset tokens.
 *
 *  - 64 random chars; only the SHA-256 hash is stored (users.registration_token / users.password_reset_token).
 *  - Single use, expiring, compared in constant time, and re-issuing invalidates the previous token.
 *  - Issuing is rate-limited per user (cooldown) and never reveals whether an e-mail exists.
 *  - Using a reset token revokes every API token and notifies the owner.
 */
class AccountTokenService
{
    public function __construct(
        private readonly NotificationService $notify,
        private readonly AuditLogger $audit,
    ) {
    }

    /* ------------------------------------------------------- registration token */

    /** Issue (or re-issue) a verification token and e-mail it. Returns false when skipped (verified / cooling down). */
    public function sendVerification(User $user, bool $force = false): bool
    {
        if ($user->email_verified_at) {
            return false;
        }
        if (! $force && $this->coolingDown($user->registration_token_sent_at)) {
            return false;
        }

        $ttl = (int) config('keystead.registration_token_ttl_minutes');

        DB::transaction(function () use ($user, $ttl, &$plain) {
            $plain = $this->newToken();
            $user->forceFill([
                'registration_token' => $this->hash($plain),
                'registration_token_expires_at' => now()->addMinutes($ttl),
                'registration_token_sent_at' => now(),
            ])->saveQuietly();
            $this->audit->log('auth.verification_sent', $user, null, null, 'Verification e-mail issued', $user);
            $this->notify->verifyRegistration($user, $this->link('verify_url', $plain, $user->email), (int) ceil($ttl / 60));
        });

        return true;
    }

    public function verifyEmail(string $email, string $token): User
    {
        $failedUser = false;
        $result = DB::transaction(function () use ($email, $token, &$failedUser) {
            $user = User::where('email', $email)->lockForUpdate()->first();

            if (! $user || ! $this->matches($user->registration_token, $user->registration_token_expires_at, $token)) {
                $failedUser = $user ?? true;

                return null;
            }

            $user->forceFill([
                'email_verified_at' => now(),
                'registration_token' => null,
                'registration_token_expires_at' => null,
            ])->saveQuietly();

            $this->audit->log('auth.email_verified', $user, null, null, 'Email address verified', $user);
            $this->notify->emailVerified($user);

            return $user;
        });

        if (! $result) {
            // Logged AFTER the transaction: throwing inside it would roll the audit row back too.
            $this->audit->log('auth.verification_failed', $failedUser instanceof User ? $failedUser : null, null, ['email' => $email], 'Invalid or expired verification token', $failedUser instanceof User ? $failedUser : null);
            throw ValidationException::withMessages(['token' => 'This verification link is invalid or has expired.']);
        }

        return $result;
    }

    /* ----------------------------------------------------- password reset token */

    /** Always succeeds from the caller's point of view (no account enumeration). */
    public function sendPasswordReset(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! $user->isActive() || $this->coolingDown($user->password_reset_token_sent_at)) {
            return;
        }

        $ttl = (int) config('keystead.password_reset_token_ttl_minutes');

        DB::transaction(function () use ($user, $ttl) {
            $plain = $this->newToken();
            $user->forceFill([
                'password_reset_token' => $this->hash($plain),
                'password_reset_token_expires_at' => now()->addMinutes($ttl),
                'password_reset_token_sent_at' => now(),
            ])->saveQuietly();
            $this->audit->log('auth.password_reset_requested', $user, null, null, 'Password reset e-mail issued', $user);
            $this->notify->passwordResetLink($user, $this->link('reset_url', $plain, $user->email), $ttl);
        });
    }

    public function resetPassword(string $email, string $token, string $newPassword, ?string $ip = null): User
    {
        $failedUser = false;
        $result = DB::transaction(function () use ($email, $token, $newPassword, $ip, &$failedUser) {
            $user = User::where('email', $email)->lockForUpdate()->first();

            if (! $user || ! $this->matches($user->password_reset_token, $user->password_reset_token_expires_at, $token)) {
                $failedUser = $user ?? true;

                return null;
            }

            $this->applyPassword($user, $newPassword, 'auth.password_reset', 'Password reset with e-mailed token', 'password reset link', $ip);

            // Owning the mailbox proves ownership of the address.
            if (! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->saveQuietly();
            }

            return $user;
        });

        if (! $result) {
            $this->audit->log('auth.password_reset_failed', $failedUser instanceof User ? $failedUser : null, null, ['email' => $email], 'Invalid or expired reset token', $failedUser instanceof User ? $failedUser : null);
            throw ValidationException::withMessages(['token' => 'This password reset link is invalid or has expired.']);
        }

        return $result;
    }

    /** Authenticated change: requires the current password; signs out every other API session. */
    public function changePassword(User $user, string $current, string $new, ?string $keepTokenId = null, ?string $ip = null): void
    {
        if (! Hash::check($current, $user->password)) {
            $this->audit->log('auth.password_change_failed', $user, null, null, 'Wrong current password', $user);
            throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
        }

        DB::transaction(function () use ($user, $new, $keepTokenId, $ip) {
            $this->applyPassword($user, $new, 'auth.password_changed', 'Password changed by the user', 'account settings', $ip, $keepTokenId);
        });
    }

    /* -------------------------------------------------------------- internals */

    private function applyPassword(User $user, string $password, string $event, string $why, string $via, ?string $ip, ?string $keepTokenId = null): void
    {
        $user->forceFill([
            'password' => $password, // hashed by the model cast
            'password_changed_at' => now(),
            'password_reset_token' => null,
            'password_reset_token_expires_at' => null,
            'remember_token' => Str::random(60),
        ])->saveQuietly();

        $user->tokens()->when($keepTokenId, fn ($q) => $q->where('id', '!=', $keepTokenId))->delete();

        $this->audit->log($event, $user, null, null, $why, $user);
        $this->notify->passwordChanged($user, $via, $ip);
    }

    private function newToken(): string
    {
        return Str::random(64);
    }

    private function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    private function matches(?string $storedHash, $expiresAt, string $plain): bool
    {
        return $storedHash !== null
            && $expiresAt !== null
            && now()->lessThanOrEqualTo($expiresAt)
            && hash_equals($storedHash, $this->hash($plain));
    }

    private function coolingDown($sentAt): bool
    {
        return $sentAt !== null && $sentAt->copy()->addSeconds((int) config('keystead.token_resend_cooldown_seconds'))->isFuture();
    }

    private function link(string $configKey, string $plain, string $email): string
    {
        $path = str_replace(['{token}', '{email}'], [$plain, urlencode($email)], (string) config("keystead.$configKey"));

        return str_starts_with($path, 'http') ? $path : rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }
}
