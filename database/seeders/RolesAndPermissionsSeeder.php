<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions
        $permissions = [
            'manage photos',
            'manage company settings',
            'manage users',
            'manage clients',
            'manage projects',
            'manage contractors',
            'manage quotes',
            'manage quote requests',
            'manage time entries',
            'manage locations',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Roles
        $superAdmin = Role::firstOrCreate(['name' => 'superadmin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $editor = Role::firstOrCreate(['name' => 'editor']);
        Role::firstOrCreate(['name' => 'client']);
        Role::firstOrCreate(['name' => 'worker']);

        // superadmin gets everything
        $superAdmin->syncPermissions(Permission::all());

        // admin gets photos, company settings, projects and quotes
        $admin->syncPermissions([
            'manage photos',
            'manage company settings',
            'manage clients',
            'manage projects',
            'manage contractors',
            'manage quotes',
            'manage quote requests',
            'manage time entries',
            'manage locations',
        ]);

        // manager has the same permissions as admin
        $manager->syncPermissions($admin->permissions);

        // editor only manages photos
        $editor->syncPermissions([
            'manage photos',
        ]);
    }
}
