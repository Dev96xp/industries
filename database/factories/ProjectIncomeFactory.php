<?php

namespace Database\Factories;

use App\Models\ProjectIncome;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectIncome>
 */
class ProjectIncomeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => fake()->sentence(4),
            'source' => fake()->randomElement(['bank_loan', 'partner', 'personal', 'client_payment', 'investor', 'other']),
            'amount' => fake()->randomFloat(2, 5000, 500000),
            'income_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
