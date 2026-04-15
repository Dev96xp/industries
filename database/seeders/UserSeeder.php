<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'name' => 'Claudia Prueba',
                'email' => 'claudia@gmail.com',
                'phone' => '13087464927',
                'address' => '1234 Dodge St',
                'city' => 'Omaha',
                'state' => 'NE',
                'zip' => '68102',
                'latitude' => 41.2565,
                'longitude' => -95.9345,
            ],
            [
                'name' => 'Fabiola Hernandez Test',
                'email' => 'fabiola@gmail.com',
                'phone' => '13087464108',
                'address' => '5678 O St',
                'city' => 'Lincoln',
                'state' => 'NE',
                'zip' => '68508',
                'latitude' => 40.8136,
                'longitude' => -96.7026,
            ],
            [
                'name' => 'Vanessa Jovanna Prueba',
                'email' => 'vanessaj@gmail.com',
                'phone' => '13087464108',
                'address' => '910 Central Ave',
                'city' => 'Kearney',
                'state' => 'NE',
                'zip' => '68847',
                'latitude' => 40.6993,
                'longitude' => -99.0817,
            ],
            [
                'name' => 'Alejandro Lopez',
                'email' => 'alejandro@gmail.com',
                'phone' => '13087464108',
                'address' => '3210 N Broad St',
                'city' => 'Fremont',
                'state' => 'NE',
                'zip' => '68025',
                'latitude' => 41.4350,
                'longitude' => -96.4983,
            ],
            [
                'name' => 'Ivonne Prueba',
                'email' => 'ivonne@gmail.com',
                'phone' => '444-564-7788',
                'address' => '7890 S Locust St',
                'city' => 'Grand Island',
                'state' => 'NE',
                'zip' => '68801',
                'latitude' => 40.9252,
                'longitude' => -98.3426,
            ],
            [
                'name' => 'Alejandra Gonzalez',
                'email' => 'alejandrag@gmail.com',
                'phone' => '402-454-9999',
                'address' => '456 Norfolk Ave',
                'city' => 'Norfolk',
                'state' => 'NE',
                'zip' => '68701',
                'latitude' => 42.0280,
                'longitude' => -97.4170,
            ],
        ];

        foreach ($clients as $data) {
            $user = User::create([
                'password' => bcrypt('12345678'),
                ...$data,
            ]);
            $user->assignRole('Client');
        }

        // 20 additional test clients across Nebraska
        User::factory(20)->client()->create();
    }
}
