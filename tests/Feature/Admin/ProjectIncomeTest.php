<?php

use App\Models\Project;
use App\Models\ProjectIncome;
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

it('can add an income entry to a project', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('manage projects');

    $project = Project::factory()->create();

    Volt::actingAs($admin)
        ->test('admin.projects.edit', ['project' => $project])
        ->set('incomeDescription', 'Bank loan disbursement')
        ->set('incomeSource', 'bank_loan')
        ->set('incomeAmount', '150000')
        ->set('incomeDate', '2026-03-01')
        ->call('saveIncome');

    expect($project->incomes()->where('description', 'Bank loan disbursement')->exists())->toBeTrue();
});

it('can edit an income entry', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('manage projects');

    $project = Project::factory()->create();
    $income = ProjectIncome::factory()->for($project)->create(['description' => 'Old desc', 'amount' => 50000, 'income_date' => '2026-03-01']);

    Volt::actingAs($admin)
        ->test('admin.projects.edit', ['project' => $project])
        ->call('editIncome', $income->id)
        ->set('incomeDescription', 'Updated desc')
        ->set('incomeAmount', '75000')
        ->call('saveIncome');

    expect($income->fresh()->description)->toBe('Updated desc');
    expect((float) $income->fresh()->amount)->toBe(75000.0);
});

it('can delete an income entry', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('manage projects');

    $project = Project::factory()->create();
    $income = ProjectIncome::factory()->for($project)->create();

    Volt::actingAs($admin)
        ->test('admin.projects.edit', ['project' => $project])
        ->call('deleteIncome', $income->id);

    expect(ProjectIncome::find($income->id))->toBeNull();
});
