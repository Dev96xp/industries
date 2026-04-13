<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Elimina carpetas de archivos subidos para evitar acumular archivos de prueba
        Storage::disk('public')->deleteDirectory('photos');
        Storage::disk('public')->deleteDirectory('project-photos');
        Storage::disk('public')->deleteDirectory('receipts');

        $this->call(RolesAndPermissionsSeeder::class);   // Ejecuta el seeder de roles y permisos antes de crear usuarios
        $this->call(UserSeeder::class);                  // Ejecuta el seeder de usuarios

        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@industries.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
            ]
        );

        $superAdmin->assignRole('superadmin');
    }
}
