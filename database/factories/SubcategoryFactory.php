<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Subcategory>
 */
class SubcategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'name_en' => null,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
