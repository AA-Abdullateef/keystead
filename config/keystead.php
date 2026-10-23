<?php

return [
    /*
    | Account tokens (registration / e-mail verification and password reset).
    | Plain tokens live only in the e-mailed link; the database stores a SHA-256 hash.
    */
    'registration_token_ttl_minutes' => (int) env('KEYSTEAD_REGISTRATION_TOKEN_TTL', 1440),
    'password_reset_token_ttl_minutes' => (int) env('KEYSTEAD_PASSWORD_RESET_TOKEN_TTL', 60),
    'token_resend_cooldown_seconds' => (int) env('KEYSTEAD_TOKEN_RESEND_COOLDOWN', 60),

    // Money-moving API actions (invest, withdraw, loan apply/pay/cancel) require a verified e-mail.
    'require_verified_email_for_money' => (bool) env('KEYSTEAD_REQUIRE_VERIFIED_EMAIL', true),

    /*
    | Link templates placed in e-mails. {token} and {email} are replaced. Point these at a SPA or
    | mobile deep link if you have one; the defaults hit the built-in confirmation pages.
    */
    'verify_url' => env('KEYSTEAD_VERIFY_URL', '/verify-email/{token}?email={email}'),
    'reset_url' => env('KEYSTEAD_RESET_URL', '/reset-password/{token}?email={email}'),

    // Idempotency-Key records are kept this long (hours).
    'idempotency_ttl_hours' => (int) env('KEYSTEAD_IDEMPOTENCY_TTL_HOURS', 24),
];
