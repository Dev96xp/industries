<?php

use App\Models\Project;
use App\Models\User;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage projects']);
});

it('renders the kanban board for authorized users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('manage projects');

    $this->actingAs($admin)
        ->get(route('admin.projects.kanban'))
        ->assertStatus(200);
});

it('moves a project forward to the next status', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('manage projects');

    $project = Project::factory()->create(['status' => 'draft']);

    Volt::actingAs($admin)
        ->test('admin.projects.kanban')
        ->call('moveForward', $project->id);

    expect($project->fresh()->status)->toBe('planning');
});

it('moves a project backward to the previous status', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('manage projects');

    $project = Project::factory()->create(['status' => 'in_progress']);

    Volt::actingAs($admin)
        ->test('admin.projects.kanban')
        ->call('moveBackward', $project->id);

    expect($project->fresh()->status)->toBe('planning');
});

it('does not move a project past the last status', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('manage projects');

    $project = Project::factory()->create(['status' => 'cancelled']);

    Volt::actingAs($admin)
        ->test('admin.projects.kanban')
        ->call('moveForward', $project->id);

    expect($project->fresh()->status)->toBe('cancelled');
});

it('does not move a project before the first status', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('manage projects');

    $project = Project::factory()->create(['status' => 'draft']);

    Volt::actingAs($admin)
        ->test('admin.projects.kanban')
        ->call('moveBackward', $project->id);

    expect($project->fresh()->status)->toBe('draft');
});
