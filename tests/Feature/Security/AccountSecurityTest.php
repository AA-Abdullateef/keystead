<?php

namespace Tests\Feature\Security;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\Security\PasswordChanged;
use App\Notifications\Security\ResetPasswordLink;
use App\Notifications\Security\VerifyRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsFinance;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase, BuildsFinance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        config(['keystead.token_resend_cooldown_seconds' => 0]);
    }

    /** Pull the plain token out of the e-mailed link; the DB only holds its hash. */
    private function tokenFrom(string $class, User $user): string
    {
        $found = null;
        Notification::assertSentTo($user, $class, function ($n) use (&$found, $user) {
            $found = (new \ReflectionClass($n))->getProperty('url')->getValue($n);

            return true;
        });
        preg_match('#/(?:verify-email|reset-password)/([A-Za-z0-9]{64})#', $found, $m);

        return $m[1];
    }

    public function test_register_sends_verification_and_stores_only_a_hash(): void
    {
        Notification::fake();
        $this->postJson('/api/v1/auth/register', ['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'Str0ng!Passw0rd', 'password_confirmation' => 'Str0ng!Passw0rd'])->assertCreated();

        $user = User::where('email', 'ada@example.test')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $plain = $this->tokenFrom(VerifyRegistration::class, $user);
        $this->assertNotSame($plain, $user->registration_token);
        $this->assertSame(hash('sha256', $plain), $user->registration_token);
        $this->assertDatabaseHas('audit_logs', ['event' => 'auth.verification_sent']);
    }

    public function test_verification_token_is_single_use_and_unlocks_money_endpoints(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->investor()->create();
        $h = $this->bearer($user);

        $this->postJson('/api/v1/withdrawals', ['amount' => '10.00'], $h)->assertForbidden();

        $this->postJson('/api/v1/auth/resend-verification', ['email' => $user->email])->assertOk();
        $plain = $this->tokenFrom(VerifyRegistration::class, $user->fresh());

        $this->postJson('/api/v1/auth/verify-email', ['email' => $user->email, 'token' => $plain])->assertOk();
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->registration_token);
        $this->postJson('/api/v1/auth/verify-email', ['email' => $user->email, 'token' => $plain])->assertStatus(422);
    }

    public function test_wrong_and_expired_tokens_are_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        app(\App\Services\AccountTokenService::class)->sendVerification($user);
        $plain = $this->tokenFrom(VerifyRegistration::class, $user->fresh());

        $this->postJson('/api/v1/auth/verify-email', ['email' => $user->email, 'token' => str_repeat('a', 64)])->assertStatus(422);
        $this->travel(2)->days();
        $this->postJson('/api/v1/auth/verify-email', ['email' => $user->email, 'token' => $plain])->assertStatus(422);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('audit_logs', ['event' => 'auth.verification_failed']);
    }

    public function test_forgot_password_gives_the_same_answer_for_unknown_emails(): void
    {
        Notification::fake();
        $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => User::factory()->create()->email]);
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.test']);

        $this->assertSame($known->status(), $unknown->status());
        $this->assertSame($known->json('message'), $unknown->json('message'));
        Notification::assertSentTimes(ResetPasswordLink::class, 1);
    }

    public function test_password_reset_changes_password_revokes_tokens_and_notifies_synchronously(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $user->createToken('old');
        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();
        $plain = $this->tokenFrom(ResetPasswordLink::class, $user->fresh());

        $this->postJson('/api/v1/auth/reset-password', ['email' => $user->email, 'token' => $plain, 'password' => 'N3w!Passw0rd#x', 'password_confirmation' => 'N3w!Passw0rd#x'])->assertOk();

        $user->refresh();
        $this->assertTrue(\Hash::check('N3w!Passw0rd#x', $user->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertNull($user->password_reset_token);
        $this->assertNotNull($user->password_changed_at);
        Notification::assertSentTo($user, PasswordChanged::class);
        $this->postJson('/api/v1/auth/reset-password', ['email' => $user->email, 'token' => $plain, 'password' => 'An0ther!Passw0rd', 'password_confirmation' => 'An0ther!Passw0rd'])->assertStatus(422);
    }

    public function test_no_notification_is_queued(): void
    {
        $this->assertNotInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, new PasswordChanged('x', '127.0.0.1'));
        $this->assertNotInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, new \App\Notifications\Financial\WalletFunded('1.00', '1.00'));
    }

    public function test_change_password_requires_current_password(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $h = $this->bearer($user);

        $this->putJson('/api/v1/auth/password', ['current_password' => 'wrong', 'password' => 'N3w!Passw0rd#x', 'password_confirmation' => 'N3w!Passw0rd#x'], $h)->assertStatus(422);
        $this->putJson('/api/v1/auth/password', ['current_password' => 'Password123!', 'password' => 'N3w!Passw0rd#x', 'password_confirmation' => 'N3w!Passw0rd#x'], $h)->assertOk();
        Notification::assertSentTo($user, PasswordChanged::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'auth.password_changed']);
    }

    public function test_audit_log_is_append_only(): void
    {
        $user = User::factory()->create();
        $log = AuditLog::firstOrFail();

        $this->expectException(\LogicException::class);
        $log->update(['description' => 'tampered']);
    }

    public function test_audit_log_cannot_be_changed_with_raw_sql(): void
    {
        User::factory()->create();
        $this->expectException(\Illuminate\Database\QueryException::class);
        \DB::table('audit_logs')->update(['description' => 'tampered']);
    }

    public function test_audit_never_stores_secrets(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        app(\App\Services\AccountTokenService::class)->sendVerification($user);

        $blob = AuditLog::all()->map(fn ($l) => json_encode([$l->old_values, $l->new_values, $l->context]))->implode('');
        $this->assertStringNotContainsString((string) $user->fresh()->registration_token, $blob);
        $this->assertStringNotContainsString($user->fresh()->password, $blob);
    }

    public function test_console_verification_page_does_not_consume_the_token_until_posted(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        app(\App\Services\AccountTokenService::class)->sendVerification($user);
        $plain = $this->tokenFrom(VerifyRegistration::class, $user->fresh());

        $this->get("/verify-email/$plain?email=" . urlencode($user->email))->assertOk();
        $this->assertNull($user->fresh()->email_verified_at);

        $this->post('/verify-email', ['email' => $user->email, 'token' => $plain])->assertRedirect('/login');
        $this->assertNotNull($user->fresh()->email_verified_at);
        Notification::assertSentTo($user, \App\Notifications\Security\EmailVerified::class);
    }

    public function test_console_login_and_failed_login_are_audited(): void
    {
        $admin = $this->admin();
        $this->post('/login', ['email' => $admin->email, 'password' => 'nope']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'auth.login_failed']);
        $this->post('/login', ['email' => $admin->email, 'password' => 'Password123!']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'auth.login']);
    }

    public function test_admin_created_user_gets_a_verification_mail(): void
    {
        Notification::fake();
        $this->actingAs($this->admin())->post('/admin/users', ['name' => 'New Person', 'email' => 'new@example.test', 'password' => 'Str0ng!Passw0rd', 'role' => 'investor', 'status' => 'active'])->assertSessionHasNoErrors();
        $created = User::where('email', 'new@example.test')->first();
        $this->assertNotNull($created);
        Notification::assertSentTo($created, VerifyRegistration::class);
    }
}
