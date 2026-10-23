<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\InvestmentProject;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InvestmentProject> */
class InvestmentProjectFactory extends Factory
{
    protected $model = InvestmentProject::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'target_amount' => '100000.00',
            'min_investment' => '1000.00',
            'management_fee_percent' => '10.00',
            'expected_roi' => '8.00',
            'start_date' => now()->subMonths(6)->toDateString(),
            'end_date' => now()->addYears(3)->toDateString(),
            'status' => ProjectStatus::Open,
            'total_raised' => '0.00',
            'created_by' => User::factory(),
        ];
    }
}
