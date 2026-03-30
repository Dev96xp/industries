<?php

namespace Database\Factories;

use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        return [
            'number' => 'Q-'.str_pad($counter, 4, '0', STR_PAD_LEFT),
            'client_name' => $this->faker->name(),
            'client_email' => $this->faker->safeEmail(),
            'client_phone' => $this->faker->phoneNumber(),
            'quote_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'expiration_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'tax_percentage' => '10.00',
            'discount' => '0.00',
            'notes' => $this->faker->optional()->sentence(),
            'terms' => $this->faker->optional()->sentence(),
            'status' => $this->faker->randomElement(['draft', 'sent', 'accepted', 'rejected']),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function accepted(): static
    {
        return $this->state(['status' => 'accepted']);
    }
}
