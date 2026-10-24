<?php

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Spatie\Permission\Models\Permission;

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});
test('admins see kpis, permitted modules and items that need attention', function () {
    collect(['manage quotes', 'manage clients', 'manage projects', 'manage quote requests'])
        ->each(fn (string $permission) => Permission::firstOrCreate(['name' => $permission]));

    $admin = User::factory()->create();
    $admin->givePermissionTo(['manage quotes', 'manage clients', 'manage projects', 'manage quote requests']);

    $overdue = Quote::factory()->invoiced()->create([
        'title' => 'Overdue roofing job',
        'invoice_due_date' => now()->subDays(3),
        'tax_percentage' => 0,
        'discount' => 0,
    ]);
    QuoteItem::factory()->create(['quote_id' => $overdue->id, 'quantity' => 1, 'unit_price' => 1200]);
    Quote::factory()->create(['status' => 'sent', 'title' => 'Awaiting kitchen job']);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Outstanding')
        ->assertSee('$1,200')
        ->assertSee('1 overdue')
        ->assertSee('Overdue roofing job')
        ->assertSee('Awaiting kitchen job')
        ->assertSee(route('admin.clients'))
        ->assertSee(route('admin.quotes', ['tab' => 'invoices']), false);
});

test('users without permissions do not see business modules or money', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Outstanding')
        ->assertDontSee(route('admin.quotes'))
        ->assertSee("You don't have access to any modules yet.", false);
});
