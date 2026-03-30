<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    public function definition(): array
    {
        $clockIn = $this->faker->dateTimeBetween('-7 days', '-2 hours');
        $clockOut = $this->faker->dateTimeBetween($clockIn, 'now');

        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'clock_in_at' => $clockIn,
            'clock_out_at' => $clockOut,
            'clock_in_verified' => false,
            'clock_out_verified' => false,
        ];
    }

    public function active(): static
    {
        return $this->state([
            'clock_out_at' => null,
            'clock_out_verified' => false,
        ]);
    }
}
