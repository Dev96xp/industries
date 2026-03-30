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
    Role::firstOrCreate(['name' => 'client']);

    Permission::firstOrCreate(['name' => 'manage photos']);
    Permission::firstOrCreate(['name' => 'manage company settings']);
    Permission::firstOrCreate(['name' => 'manage users']);
});

it('assigns client role automatically on registration', function () {
    Volt::test('auth.register')
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $user = User::where('email', 'jane@example.com')->first();

    expect($user->hasRole('client'))->toBeTrue();
});

it('redirects new registrations to the home page', function () {
    Volt::test('auth.register')
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('home'));
});

it('blocks clients from accessing the dashboard', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $this->actingAs($client)->get(route('dashboard'))->assertRedirect(route('home'));
});

it('blocks clients from accessing settings', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $this->actingAs($client)->get('/settings/profile')->assertRedirect(route('home'));
});

it('allows staff users to access the dashboard', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get(route('dashboard'))->assertOk();
});

it('shows clients in the admin users panel', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');
    $superadmin->givePermissionTo('manage users');

    $client = User::factory()->create(['name' => 'Test Client']);
    $client->assignRole('client');

    Volt::actingAs($superadmin)
        ->test('admin.users')
        ->set('activeTab', 'clients')
        ->assertSee('Test Client');
});

it('superadmin can promote a client to editor', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');
    $superadmin->givePermissionTo('manage users');

    $client = User::factory()->create();
    $client->assignRole('client');

    Volt::actingAs($superadmin)
        ->test('admin.users')
        ->call('editUser', $client->id)
        ->set('selectedRole', 'editor')
        ->call('saveRole');

    expect($client->fresh()->hasRole('editor'))->toBeTrue();
    expect($client->fresh()->hasRole('client'))->toBeFalse();
});
