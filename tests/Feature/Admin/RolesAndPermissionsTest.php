<?php

use App\Models\User;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'superadmin']);
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'editor']);

    Permission::firstOrCreate(['name' => 'manage photos']);
    Permission::firstOrCreate(['name' => 'manage company settings']);
    Permission::firstOrCreate(['name' => 'manage users']);
});

it('redirects guests from admin routes', function () {
    $this->get(route('admin.photos'))->assertRedirect(route('login'));
    $this->get(route('admin.company-settings'))->assertRedirect(route('login'));
    $this->get(route('admin.users'))->assertRedirect(route('login'));
});

it('blocks users without permission from photos', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.photos'))->assertForbidden();
});

it('allows users with manage photos permission to access photos', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage photos');

    $this->actingAs($user)->get(route('admin.photos'))->assertOk();
});

it('blocks users without permission from company settings', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage photos');

    $this->actingAs($user)->get(route('admin.company-settings'))->assertForbidden();
});

it('allows superadmin to access all modules', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');
    $user->givePermissionTo(['manage photos', 'manage company settings', 'manage users']);

    $this->actingAs($user)->get(route('admin.photos'))->assertOk();
    $this->actingAs($user)->get(route('admin.company-settings'))->assertOk();
    $this->actingAs($user)->get(route('admin.users'))->assertOk();
});

it('superadmin can assign a role to a user', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');
    $superadmin->givePermissionTo('manage users');

    $targetUser = User::factory()->create();

    Volt::actingAs($superadmin)
        ->test('admin.users')
        ->call('editUser', $targetUser->id)
        ->set('selectedRole', 'editor')
        ->call('saveRole');

    expect($targetUser->fresh()->hasRole('editor'))->toBeTrue();
});

it('superadmin can remove a role from a user', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');
    $superadmin->givePermissionTo('manage users');

    $targetUser = User::factory()->create();
    $targetUser->assignRole('editor');

    Volt::actingAs($superadmin)
        ->test('admin.users')
        ->call('editUser', $targetUser->id)
        ->set('selectedRole', '')
        ->call('saveRole');

    expect($targetUser->fresh()->roles)->toBeEmpty();
});
