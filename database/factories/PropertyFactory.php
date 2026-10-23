<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Property> */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'name' => fake()->streetName() . ' ' . fake()->randomElement(['Court', 'Residences', 'Plaza', 'Heights']),
            'type' => 'investment',
            'description' => fake()->sentence(12),
            'location' => fake()->city(),
            'purchase_price' => '500000.00',
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    public function forLoans(): static
    {
        return $this->state(['type' => 'loan']);
    }
}
