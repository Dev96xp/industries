<?php

use App\Models\Project;
use App\Models\Quote;
use App\Models\QuoteItem;
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

    Permission::firstOrCreate(['name' => 'manage quotes']);
    Permission::firstOrCreate(['name' => 'manage projects']);
});

it('redirects guests from the quotes index', function () {
    $this->get(route('admin.quotes'))->assertRedirect(route('login'));
});

it('blocks users without permission from quotes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.quotes'))->assertForbidden();
});

it('allows users with manage quotes permission to access quotes', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage quotes');

    $this->actingAs($user)->get(route('admin.quotes'))->assertOk();
});

it('admin can create a quote linked to a registered client', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage quotes');

    $client = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $client->assignRole('client');

    Volt::actingAs($admin)
        ->test('admin.quotes.create')
        ->set('user_id', $client->id)
        ->set('quote_date', now()->format('Y-m-d'))
        ->set('items', [
            ['description' => 'Labor', 'quantity' => '10', 'unit_price' => '150'],
        ])
        ->call('save');

    $quote = Quote::where('user_id', $client->id)->first();

    expect($quote)->not->toBeNull()
        ->and($quote->number)->toStartWith('Q-')
        ->and($quote->client_name)->toBe('Jane Doe')
        ->and($quote->items)->toHaveCount(1);
});

it('requires a registered client to create a quote', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage quotes');

    Volt::actingAs($admin)
        ->test('admin.quotes.create')
        ->set('quote_date', now()->format('Y-m-d'))
        ->set('items', [
            ['description' => 'Labor', 'quantity' => '10', 'unit_price' => '150'],
        ])
        ->call('save')
        ->assertHasErrors(['user_id']);
});

it('generates sequential quote numbers', function () {
    Quote::factory()->create(['number' => 'Q-0005']);

    expect(Quote::generateNumber())->toBe('Q-0006');
});

it('create quote page pre-selects client from query param', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage quotes');

    $client = User::factory()->create();
    $client->assignRole('client');

    $this->actingAs($admin)
        ->get(route('admin.quotes.create', ['client' => $client->id]))
        ->assertOk();
});

it('admin can update an existing quote', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage quotes');

    $client = User::factory()->create();
    $quote = Quote::factory()->create(['user_id' => $client->id, 'client_name' => $client->name, 'status' => 'draft']);
    QuoteItem::factory()->create(['quote_id' => $quote->id, 'description' => 'Old item', 'quantity' => 1, 'unit_price' => 100, 'sort_order' => 0]);

    Volt::actingAs($admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->set('status', 'sent')
        ->call('save');

    expect($quote->fresh()->status)->toBe('sent');
});

it('admin can delete a quote', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage quotes');

    $quote = Quote::factory()->create();

    Volt::actingAs($admin)
        ->test('admin.quotes.index')
        ->call('deleteQuote', $quote->id);

    expect(Quote::find($quote->id))->toBeNull();
});

it('can convert a quote to a project', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage quotes');
    $admin->givePermissionTo('manage projects');

    $client = User::factory()->create(['name' => 'Big Build Client']);
    $quote = Quote::factory()->create(['user_id' => $client->id, 'client_name' => $client->name, 'status' => 'draft']);
    QuoteItem::factory()->create(['quote_id' => $quote->id, 'description' => 'Foundation', 'quantity' => 1, 'unit_price' => 5000, 'sort_order' => 0]);

    Volt::actingAs($admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->call('convertToProject');

    $quote->refresh();
    expect($quote->project_id)->not->toBeNull()
        ->and($quote->status)->toBe('accepted')
        ->and(Project::find($quote->project_id)->client_user_id)->toBe($client->id);
});

it('cannot convert an already-linked quote to a project', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage quotes');
    $admin->givePermissionTo('manage projects');

    $project = Project::factory()->create();
    $quote = Quote::factory()->create(['project_id' => $project->id, 'status' => 'accepted']);

    Volt::actingAs($admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->call('convertToProject');

    expect(Project::count())->toBe(1);
});
