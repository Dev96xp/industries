<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'name_en' => null,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'sort_order' => 0,
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
