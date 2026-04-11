<?php

use App\Models\CompanySetting;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    CompanySetting::query()->delete();
});

test('dashboard is accessible when ip restriction is disabled', function () {
    CompanySetting::create([
        'company_name' => 'Test',
        'ip_restriction_enabled' => false,
        'allowed_ips' => null,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200);
});

test('dashboard is accessible when ip is in the allowed list', function () {
    CompanySetting::create([
        'company_name' => 'Test',
        'ip_restriction_enabled' => true,
        'allowed_ips' => '127.0.0.1, 10.0.0.1',
    ]);

    $user = User::factory()->create();

    // Test requests come from 127.0.0.1 by default
    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200);
});

test('dashboard returns 403 when ip restriction is enabled and ip is not allowed', function () {
    CompanySetting::create([
        'company_name' => 'Test',
        'ip_restriction_enabled' => true,
        'allowed_ips' => '10.0.0.5, 192.168.1.1',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(403);
});

test('dashboard is accessible when ip restriction is enabled but allowed list is empty', function () {
    CompanySetting::create([
        'company_name' => 'Test',
        'ip_restriction_enabled' => true,
        'allowed_ips' => '',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200);
});

test('superadmin is never blocked by ip restriction', function () {
    CompanySetting::create([
        'company_name' => 'Test',
        'ip_restriction_enabled' => true,
        'allowed_ips' => '10.0.0.5',
    ]);

    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');

    $this->actingAs($superadmin)
        ->get('/dashboard')
        ->assertStatus(200);
});

test('public home page is never blocked by ip restriction', function () {
    CompanySetting::create([
        'company_name' => 'Test',
        'ip_restriction_enabled' => true,
        'allowed_ips' => '10.0.0.5',
    ]);

    $this->get('/')
        ->assertStatus(200);
});
