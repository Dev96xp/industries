<?php

namespace Database\Factories;

use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Photo>
 */
class PhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'path' => 'photos/' . $this->faker->uuid() . '.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(100000, 5000000),
            'category' => $this->faker->randomElement(['residential', 'commercial', 'industrial', 'renovation']),
            'is_featured' => false,
            'is_hero'     => false,
            'is_about'    => false,
            'sort_order'  => 0,
        ];
    }
}
