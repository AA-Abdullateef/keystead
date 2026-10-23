<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFinance;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase, BuildsFinance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_registration_returns_envelope_token_and_an_investor_with_a_wallet(): void
    {
        $r = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ada Obi', 'email' => 'ada@example.test',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ]);

        $r->assertCreated()->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data' => ['user' => ['id', 'email', 'roles'], 'access_token', 'token_type']]);
        $user = User::where('email', 'ada@example.test')->first();
        $this->assertTrue($user->hasRole('investor'));
        $this->assertSame('0.00', $user->wallet()->first()->balance);
    }

    public function test_registration_cannot_grant_a_role_or_status(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Mallory', 'email' => 'm@example.test', 'role' => 'admin', 'status' => 'active', 'roles' => ['admin'],
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ])->assertCreated();

        $this->assertFalse(User::where('email', 'm@example.test')->first()->isAdmin());
    }

    public function test_validation_errors_use_the_envelope(): void
    {
        $this->postJson('/api/v1/auth/register', ['email' => 'nope'])
            ->assertStatus(422)->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors' => ['name', 'email', 'password']]);
    }

    public function test_login_success_and_uniform_failure_message(): void
    {
        $user = User::factory()->investor()->create(['email' => 'a@example.test']);

        $this->postJson('/api/v1/auth/login', ['email' => 'a@example.test', 'password' => 'Password123!'])
            ->assertOk()->assertJsonStructure(['data' => ['access_token']]);

        $wrong = $this->postJson('/api/v1/auth/login', ['email' => 'a@example.test', 'password' => 'bad'])->assertStatus(422);
        $unknown = $this->postJson('/api/v1/auth/login', ['email' => 'zz@example.test', 'password' => 'bad'])->assertStatus(422);
        $this->assertSame($wrong->json('errors.email'), $unknown->json('errors.email'));
    }

    public function test_suspended_users_cannot_log_in_or_use_an_existing_token(): void
    {
        $user = User::factory()->suspended()->create(['email' => 's@example.test']);
        $this->postJson('/api/v1/auth/login', ['email' => 's@example.test', 'password' => 'Password123!'])->assertForbidden();

        $active = User::factory()->investor()->create();
        $headers = $this->bearer($active);
        $active->forceFill(['status' => 'suspended'])->save();
        $this->getJson('/api/v1/wallet', $headers)->assertForbidden();
    }

    public function test_protected_routes_need_a_token_and_logout_revokes_it(): void
    {
        $this->getJson('/api/v1/wallet')->assertUnauthorized()->assertJsonPath('success', false);

        $user = User::factory()->investor()->create();
        $h = $this->bearer($user);
        $this->postJson('/api/v1/auth/logout', [], $h)->assertOk();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_forgot_password_does_not_reveal_whether_an_account_exists(): void
    {
        User::factory()->investor()->create(['email' => 'real@example.test']);
        $a = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'real@example.test']);
        $b = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'ghost@example.test']);
        $a->assertOk();
        $b->assertOk();
        $this->assertSame($a->json('message'), $b->json('message'));
    }

    public function test_unknown_api_routes_return_a_json_404(): void
    {
        $this->getJson('/api/v1/nothing-here')->assertNotFound()->assertJsonPath('success', false);
    }

    public function test_auth_endpoints_are_rate_limited(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $last = $this->postJson('/api/v1/auth/login', ['email' => 'x@example.test', 'password' => 'bad']);
        }
        $last->assertStatus(429);
    }
}
