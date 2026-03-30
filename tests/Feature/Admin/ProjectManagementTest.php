<?php

use App\Models\Project;
use App\Models\User;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'superadmin']);
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'client']);

    Permission::firstOrCreate(['name' => 'manage projects']);
    Permission::firstOrCreate(['name' => 'manage photos']);
    Permission::firstOrCreate(['name' => 'manage company settings']);
    Permission::firstOrCreate(['name' => 'manage users']);
});

it('redirects guests from the projects index', function () {
    $this->get(route('admin.projects'))->assertRedirect(route('login'));
});

it('blocks users without permission from projects', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.projects'))->assertForbidden();
});

it('allows users with manage projects permission to access projects', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage projects');

    $this->actingAs($user)->get(route('admin.projects'))->assertOk();
});

it('admin can create a project', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('manage projects');

    Volt::actingAs($admin)
        ->test('admin.projects.create')
        ->set('name', 'Test House Build')
        ->set('status', 'planning')
        ->set('budget', '250000')
        ->call('save');

    expect(Project::where('name', 'Test House Build')->exists())->toBeTrue();
});

it('admin can update a project', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('manage projects');

    $project = Project::factory()->create(['name' => 'Old Name']);

    Volt::actingAs($admin)
        ->test('admin.projects.edit', ['project' => $project])
        ->set('name', 'Updated Name')
        ->call('save');

    expect($project->fresh()->name)->toBe('Updated Name');
});

it('admin can archive a project', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('manage projects');

    $project = Project::factory()->create(['is_archived' => false]);

    Volt::actingAs($admin)
        ->test('admin.projects.index')
        ->call('archiveProject', $project->id);

    expect($project->fresh()->is_archived)->toBeTrue();
});

it('admin can restore an archived project', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('manage projects');

    $project = Project::factory()->create(['is_archived' => true]);

    Volt::actingAs($admin)
        ->test('admin.projects.index')
        ->call('restoreProject', $project->id);

    expect($project->fresh()->is_archived)->toBeFalse();
});
