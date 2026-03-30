<?php

namespace Database\Factories;

use App\Models\ProjectExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectExpense>
 */
class ProjectExpenseFactory extends Factory
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
            'category' => fake()->randomElement(['materials', 'labor', 'equipment', 'subcontractors', 'permits', 'other']),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'expense_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
