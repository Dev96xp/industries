<?php

namespace Database\Factories;

use App\Models\Contractor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contractor>
 */
class ContractorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $specialties = ['Electrical', 'Plumbing', 'Carpentry', 'HVAC', 'Roofing', 'Masonry', 'Painting', 'Flooring', 'Landscaping', 'Concrete'];

        return [
            'company_name' => fake()->company(),
            'contact_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'phone_secondary' => fake()->optional()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address' => fake()->address(),
            'specialty' => fake()->randomElement($specialties),
            'notes' => fake()->optional()->sentence(),
            'is_active' => fake()->boolean(85),
        ];
    }
}
