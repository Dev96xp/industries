<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['planning', 'in_progress', 'completed']),
            'address' => fake()->address(),
            'start_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'estimated_completion_date' => fake()->dateTimeBetween('now', '+1 year'),
            'budget' => fake()->randomFloat(2, 50000, 2000000),
            'internal_notes' => fake()->optional()->paragraph(),
            'is_featured' => false,
        ];
    }
}
