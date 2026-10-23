<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password123!'),
            'remember_token' => \Illuminate\Support\Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function suspended(): static
    {
        return $this->afterCreating(fn (User $u) => $u->forceFill(['status' => 'suspended'])->save());
    }

    public function admin(): static
    {
        return $this->afterCreating(fn (User $u) => $u->assignRole(User::ROLE_ADMIN));
    }

    public function manager(): static
    {
        return $this->afterCreating(fn (User $u) => $u->assignRole(User::ROLE_MANAGER));
    }

    public function investor(): static
    {
        return $this->afterCreating(fn (User $u) => $u->assignRole(User::ROLE_INVESTOR));
    }

    /** Investor with a funded wallet (funded through the ledger, like production). */
    public function funded(string $amount = '10000.00'): static
    {
        return $this->investor()->afterCreating(function (User $u) use ($amount) {
            app(\App\Services\WalletService::class)->getWallet($u->id);
            if (bccomp($amount, '0', 2) > 0) {
                app(\App\Services\WalletService::class)->deposit($u->id, $amount, $u->id, 'Test funding');
            }
        });
    }
}
