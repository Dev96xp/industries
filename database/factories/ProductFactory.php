<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $units = ['each', 'sqft', 'hr', 'day', 'lb', 'ton', 'yd', 'bag'];

        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'unit_price' => fake()->randomFloat(2, 10, 5000),
            'unit' => fake()->randomElement($units),
            'category_id' => Category::factory(),
            'is_active' => true,
        ];
    }
}
