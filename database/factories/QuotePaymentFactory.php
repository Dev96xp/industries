<?php

namespace Database\Factories;

use App\Models\Quote;
use App\Models\QuotePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuotePayment>
 */
class QuotePaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quote_id' => Quote::factory(),
            'amount' => $this->faker->randomFloat(2, 50, 5000),
            'paid_at' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'method' => $this->faker->randomElement(['cash', 'check', 'transfer', 'card']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
