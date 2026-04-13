<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Registro ingresado manualmente

        $user = User::create([
            'name' => 'Claudia Prueba',
            'email' => 'claudia@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '13087464927',
        ]);
        $user->assignRole('Client');

        $user = User::create([
            'name' => 'Fabiola Hernandez Test',
            'email' => 'fabiola@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '13087464108',
        ]);
        $user->assignRole('Client');

        $user = User::create([
            'name' => 'Vanessa Jovanna Prueba',
            'email' => 'vanessaj@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '13087464108',
        ]);
        $user->assignRole('Client');

        $user = User::create([
            'name' => 'Alejandro Lopez',
            'email' => 'alejandro@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '13087464108',
        ]);
        $user->assignRole('Client');

                $user = User::create([
            'name' => 'Ivonne Prueba',
            'email' => 'ivonne@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '444-564-7788',
        ]);
        $user->assignRole('Client');

        $user = User::create([
            'name' => 'Alejandra Gonzalez',
            'email' => 'alejandrag@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '402-454-9999',
        ]);
        $user->assignRole('Client');

        // Crear otros 20 usuarios mas
        // User::factory(4)->create();       //Lo comente pq no se puede usar en laravel forge 2024
    }
}
