<?php

namespace Tests\Concerns;

use App\Enums\LoanType;
use App\Models\InvestmentProject;
use App\Models\LoanProject;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RoleSeeder;

trait BuildsFinance
{
    protected function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    protected function investor(string $funds = '10000.00'): User
    {
        return User::factory()->funded($funds)->create();
    }

    protected function project(array $overrides = []): InvestmentProject
    {
        return InvestmentProject::factory()->create($overrides);
    }

    protected function loanProject(LoanType $type = LoanType::Mortgage, array $overrides = []): LoanProject
    {
        return LoanProject::factory()->create(array_merge(['loan_type' => $type], $overrides));
    }

    protected function bearer(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $user->createToken('tests')->plainTextToken, 'Accept' => 'application/json'];
    }

    protected function balance(User $user): string
    {
        return $user->wallet()->first()->balance;
    }

    /** [month, year] of the previous calendar month, always inside the factory project's start window. */
    protected function lastMonth(int $back = 1): array
    {
        $d = now()->startOfMonth()->subMonths($back);

        return [(int) $d->format('n'), (int) $d->format('Y')];
    }
}
