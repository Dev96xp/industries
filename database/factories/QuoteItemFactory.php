<?php

namespace Database\Factories;

use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteItem>
 */
class QuoteItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quote_id' => Quote::factory(),
            'description' => $this->faker->sentence(4),
            'quantity' => $this->faker->randomFloat(2, 1, 20),
            'unit_price' => $this->faker->randomFloat(2, 50, 5000),
            'sort_order' => 0,
        ];
    }
}
