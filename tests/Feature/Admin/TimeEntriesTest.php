<?php

use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'superadmin']);
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'worker']);
    Role::firstOrCreate(['name' => 'client']);

    Permission::firstOrCreate(['name' => 'manage time entries']);
    Permission::firstOrCreate(['name' => 'manage projects']);
});

it('worker can access the timeclock page', function () {
    $worker = User::factory()->create();
    $worker->assignRole('worker');

    $this->actingAs($worker)->get(route('worker.timeclock'))->assertOk();
});

it('guest cannot access the timeclock page', function () {
    $this->get(route('worker.timeclock'))->assertRedirect(route('login'));
});

it('worker can clock in to a project', function () {
    $worker = User::factory()->create();
    $worker->assignRole('worker');
    $project = Project::factory()->create(['is_archived' => false]);

    Volt::actingAs($worker)
        ->test('worker.timeclock')
        ->set('project_id', $project->id)
        ->call('clockIn');

    $entry = TimeEntry::where('user_id', $worker->id)->first();
    expect($entry)->not->toBeNull()
        ->and($entry->clock_out_at)->toBeNull()
        ->and($entry->project_id)->toBe($project->id);
});

it('worker cannot clock in twice without clocking out', function () {
    $worker = User::factory()->create();
    $project = Project::factory()->create(['is_archived' => false]);

    TimeEntry::factory()->create([
        'user_id' => $worker->id,
        'project_id' => $project->id,
        'clock_in_at' => now()->subHour(),
        'clock_out_at' => null,
    ]);

    Volt::actingAs($worker)
        ->test('worker.timeclock')
        ->set('project_id', $project->id)
        ->call('clockIn');

    expect(TimeEntry::where('user_id', $worker->id)->count())->toBe(1);
});

it('worker can clock out', function () {
    $worker = User::factory()->create();
    $project = Project::factory()->create(['is_archived' => false]);

    $entry = TimeEntry::factory()->create([
        'user_id' => $worker->id,
        'project_id' => $project->id,
        'clock_in_at' => now()->subHour(),
        'clock_out_at' => null,
    ]);

    Volt::actingAs($worker)
        ->test('worker.timeclock')
        ->call('clockOut');

    expect($entry->fresh()->clock_out_at)->not->toBeNull();
});

it('verifies worker is within project radius', function () {
    $project = Project::factory()->create([
        'latitude' => 25.7617,
        'longitude' => -80.1918,
        'geo_radius' => 100,
    ]);

    // Same coordinates — within radius
    expect(TimeEntry::isWithinRadius($project, 25.7617, -80.1918))->toBeTrue();

    // Far away — outside radius
    expect(TimeEntry::isWithinRadius($project, 34.0522, -118.2437))->toBeFalse();
});

it('admin can view time entries', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage time entries');

    $this->actingAs($admin)->get(route('admin.time-entries'))->assertOk();
});

it('blocks users without permission from time entries', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.time-entries'))->assertForbidden();
});
