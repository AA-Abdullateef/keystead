<?php

namespace Database\Factories;

use App\Enums\LoanType;
use App\Models\LoanProject;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LoanProject> */
class LoanProjectFactory extends Factory
{
    protected $model = LoanProject::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory()->forLoans(),
            'loan_type' => LoanType::Mortgage,
            'property_value' => '120000.00',
            'min_initial_payment' => '20000.00',
            'interest_rate' => '10.00',
            'duration_months' => 12,
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    public function property(): static
    {
        return $this->state(['loan_type' => LoanType::Property]);
    }
}
