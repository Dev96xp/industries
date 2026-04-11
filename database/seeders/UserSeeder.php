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
            'name' => 'Claudia Ramirez',
            'email' => 'claudia@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '13087464927',
        ]);
        // Asignacion de administrador
        $user->assignRole('Client');

        $user = User::create([
            'name' => 'Fabiola Hernandez',
            'email' => 'fabiola@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '13087464108',
        ]);
        // Asignacion de administrador
        $user->assignRole('Client');

        $user = User::create([
            'name' => 'Vanessa Jovanna',
            'email' => 'vanessaj@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '13087464108',
        ]);
        // Asignacion de administrador
        $user->assignRole('Client');

        $user = User::create([
            'name' => 'Alejandro Lopez',
            'email' => 'alejandro@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '13087464108',
        ]);

        // Asignacion de administrador
        $user->assignRole('Client');

        // Crear otros 20 usuarios mas
        // User::factory(4)->create();       //Lo comente pq no se puede usar en laravel forge 2024
    }
}
