<?php

use App\Models\Project;
use App\Models\ProjectExpense;
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

it('can add an expense to a project', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('manage projects');

    $project = Project::factory()->create();

    Volt::actingAs($admin)
        ->test('admin.projects.edit', ['project' => $project])
        ->set('expenseDescription', 'Concrete delivery')
        ->set('expenseCategory', 'materials')
        ->set('expenseAmount', '3500')
        ->set('expenseDate', '2026-03-01')
        ->call('saveExpense');

    expect($project->expenses()->where('description', 'Concrete delivery')->exists())->toBeTrue();
});

it('can edit an expense', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('manage projects');

    $project = Project::factory()->create();
    $expense = ProjectExpense::factory()->for($project)->create(['description' => 'Old desc', 'amount' => 1000, 'expense_date' => '2026-03-01']);

    Volt::actingAs($admin)
        ->test('admin.projects.edit', ['project' => $project])
        ->call('editExpense', $expense->id)
        ->set('expenseDescription', 'New desc')
        ->set('expenseAmount', '2000')
        ->call('saveExpense');

    expect($expense->fresh()->description)->toBe('New desc');
    expect((float) $expense->fresh()->amount)->toBe(2000.0);
});

it('can delete an expense', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('manage projects');

    $project = Project::factory()->create();
    $expense = ProjectExpense::factory()->for($project)->create();

    Volt::actingAs($admin)
        ->test('admin.projects.edit', ['project' => $project])
        ->call('deleteExpense', $expense->id);

    expect(ProjectExpense::find($expense->id))->toBeNull();
});

it('preserves existing receipt path when saving expense without a new receipt', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('manage projects');

    $project = Project::factory()->create();
    $expense = ProjectExpense::factory()->for($project)->create([
        'receipt_path' => 'receipts/existing.jpg',
        'expense_date' => '2026-03-01',
    ]);

    Volt::actingAs($admin)
        ->test('admin.projects.edit', ['project' => $project])
        ->call('editExpense', $expense->id)
        ->set('expenseDescription', 'Updated description')
        ->call('saveExpense');

    expect($expense->fresh()->receipt_path)->toBe('receipts/existing.jpg');
});
