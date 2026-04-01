<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectTask>
 */
class ProjectTaskFactory extends Factory
{
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $end = $this->faker->dateTimeBetween($start, '+3 months');

        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->sentence(),
            'start_date' => $start,
            'end_date' => $end,
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed', 'delayed']),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'assigned_type' => 'internal',
            'assigned_user_id' => null,
            'assigned_company' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
