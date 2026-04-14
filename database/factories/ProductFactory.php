<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $categories = ['materials', 'labor', 'equipment', 'permits', 'other'];
        $units = ['each', 'sqft', 'hr', 'day', 'lb', 'ton', 'yd', 'bag'];

        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'unit_price' => fake()->randomFloat(2, 10, 5000),
            'unit' => fake()->randomElement($units),
            'category' => fake()->randomElement($categories),
            'is_active' => true,
        ];
    }
}
