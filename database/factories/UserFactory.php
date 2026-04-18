<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\User;
use Faker\Generator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /** @var array<int, array{lat: float, lng: float}> */
    protected static array $nebraskaCoords = [
        ['lat' => 41.2565, 'lng' => -95.9345], // Omaha
        ['lat' => 41.2730, 'lng' => -96.0152],
        ['lat' => 41.2418, 'lng' => -95.8918],
        ['lat' => 41.3100, 'lng' => -95.9612],
        ['lat' => 41.1922, 'lng' => -96.0312],
        ['lat' => 40.8136, 'lng' => -96.7026], // Lincoln
        ['lat' => 40.8258, 'lng' => -96.6852],
        ['lat' => 40.7999, 'lng' => -96.7312],
        ['lat' => 40.8512, 'lng' => -96.6612],
        ['lat' => 40.7812, 'lng' => -96.7001],
        ['lat' => 41.1403, 'lng' => -100.7601], // North Platte
        ['lat' => 42.4928, 'lng' => -96.4003], // South Sioux City
        ['lat' => 41.4525, 'lng' => -97.3653], // Columbus
        ['lat' => 40.6993, 'lng' => -99.0817], // Kearney
        ['lat' => 42.3677, 'lng' => -101.7404], // Alliance
        ['lat' => 41.8406, 'lng' => -103.6927], // Scottsbluff
        ['lat' => 40.9252, 'lng' => -98.3426], // Grand Island
        ['lat' => 41.5350, 'lng' => -96.4603], // Fremont
        ['lat' => 40.5220, 'lng' => -101.6449], // Imperial
        ['lat' => 42.8735, 'lng' => -97.3970], // Norfolk
    ];

    public function definition(): array
    {
        $faker = app(Generator::class);
        $coords = $faker->randomElement(static::$nebraskaCoords);

        return [
            'name' => $faker->name(),
            'email' => $faker->unique()->safeEmail(),
            'phone' => $faker->numerify('###-###-####'),
            'address' => $faker->streetAddress(),
            'city' => $faker->randomElement(['Omaha', 'Lincoln', 'Kearney', 'Grand Island', 'Fremont', 'Columbus', 'Norfolk', 'North Platte', 'Scottsbluff']),
            'state' => 'NE',
            'zip' => $faker->numerify('6####'),
            'latitude' => $coords['lat'] + $faker->randomFloat(4, -0.02, 0.02),
            'longitude' => $coords['lng'] + $faker->randomFloat(4, -0.02, 0.02),
            'location_id' => Location::inRandomOrder()->value('id'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function client(): static
    {
        return $this->afterCreating(function ($user) {
            $user->assignRole('client');
        });
    }
}
