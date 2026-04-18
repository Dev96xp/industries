<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Lincoln',
                'address' => '1420 N 56th St',
                'city' => 'Lincoln',
                'state' => 'NE',
                'zip' => '68503',
                'phone' => '402-555-1000',
                'email' => 'lincoln@nucleusindustries.com',
                'is_active' => true,
            ],
            [
                'name' => 'Omaha',
                'address' => '7800 W Dodge Rd',
                'city' => 'Omaha',
                'state' => 'NE',
                'zip' => '68114',
                'phone' => '402-555-2000',
                'email' => 'omaha@nucleusindustries.com',
                'is_active' => true,
            ],
            [
                'name' => 'Grand Island',
                'address' => '3400 W State St',
                'city' => 'Grand Island',
                'state' => 'NE',
                'zip' => '68803',
                'phone' => '308-555-3000',
                'email' => 'grandisland@nucleusindustries.com',
                'is_active' => true,
            ],
            [
                'name' => 'Kansas City',
                'address' => '4800 Main St',
                'city' => 'Kansas City',
                'state' => 'MO',
                'zip' => '64112',
                'phone' => '816-555-4000',
                'email' => 'kansascity@nucleusindustries.com',
                'is_active' => true,
            ],
        ];

        foreach ($locations as $data) {
            Location::create($data);
        }
    }
}
