<?php

use App\Models\Product;
use App\Models\Project;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\QuotePayment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
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
    $product = Product::factory()->create();

    Volt::actingAs($admin)
        ->test('admin.quotes.create')
        ->set('user_id', $client->id)
        ->set('title', 'Kitchen remodel')
        ->set('quote_date', now()->format('Y-m-d'))
        ->set('items', [
            ['product_id' => $product->id, 'description' => 'Labor', 'quantity' => '10', 'unit_price' => '150'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $quote = Quote::where('user_id', $client->id)->first();

    expect($quote)->not->toBeNull()
        ->and($quote->number)->toStartWith('Q-')
        ->and($quote->title)->toBe('Kitchen remodel')
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

it('requires a job name to create a quote', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage quotes');

    $client = User::factory()->create();
    $client->assignRole('client');

    Volt::actingAs($admin)
        ->test('admin.quotes.create')
        ->set('user_id', $client->id)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);
});

it('can search quotes by job name', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage quotes');

    Quote::factory()->create(['number' => 'Q-0101', 'title' => 'Kitchen remodel']);
    Quote::factory()->create(['number' => 'Q-0102', 'title' => 'Roofing']);

    Volt::actingAs($admin)
        ->test('admin.quotes.index')
        ->set('search', 'Kitchen')
        ->assertSee('Q-0101')
        ->assertDontSee('Q-0102');
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
    $product = Product::factory()->create();
    QuoteItem::factory()->create(['quote_id' => $quote->id, 'product_id' => $product->id, 'description' => 'Old item', 'quantity' => 1, 'unit_price' => 100, 'sort_order' => 0]);

    Volt::actingAs($admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->set('title', 'Roofing')
        ->set('status', 'sent')
        ->call('save')
        ->assertHasNoErrors();

    expect($quote->fresh())
        ->status->toBe('sent')
        ->title->toBe('Roofing');
});

it('admin can delete a quote', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage quotes');

    $quote = Quote::factory()->create();

    Volt::actingAs($admin)
        ->test('admin.quotes.index')
        ->call('archiveQuote', $quote->id);

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

it('can change the client of a quote', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage quotes');

    $oldClient = User::factory()->create();
    $oldClient->assignRole('client');
    $newClient = User::factory()->create(['name' => 'New Client', 'email' => 'new@example.com', 'phone' => '555-0100']);
    $newClient->assignRole('client');

    $oldProject = Project::factory()->create(['client_user_id' => $oldClient->id]);
    $quote = Quote::factory()->create([
        'user_id' => $oldClient->id,
        'client_name' => $oldClient->name,
        'project_id' => $oldProject->id,
        'status' => 'draft',
    ]);

    Volt::actingAs($admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->call('startChangingClient')
        ->assertSet('changingClient', true)
        ->set('newClientId', $newClient->id)
        ->call('changeClient')
        ->assertHasNoErrors()
        ->assertSet('changingClient', false)
        ->assertSet('projectId', null);

    expect($quote->fresh())
        ->user_id->toBe($newClient->id)
        ->client_name->toBe('New Client')
        ->client_email->toBe('new@example.com')
        ->client_phone->toBe('555-0100')
        ->project_id->toBeNull();
});

it('cannot change the client of an invoice or a quote with payments', function (string $case) {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage quotes');

    $oldClient = User::factory()->create();
    $newClient = User::factory()->create();
    $newClient->assignRole('client');

    $quote = $case === 'invoice'
        ? Quote::factory()->invoiced()->create(['user_id' => $oldClient->id])
        : Quote::factory()->create(['user_id' => $oldClient->id]);

    if ($case === 'payments') {
        QuotePayment::factory()->create(['quote_id' => $quote->id, 'amount' => 100]);
    }

    Volt::actingAs($admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->set('newClientId', $newClient->id)
        ->call('changeClient');

    expect($quote->fresh()->user_id)->toBe($oldClient->id);
})->with(['invoice', 'payments']);

it('updates client details on quotes but not invoices when the client is edited', function () {
    Http::fake();
    Permission::firstOrCreate(['name' => 'manage users']);

    $admin = User::factory()->create();
    $admin->givePermissionTo('manage users');

    $client = User::factory()->create(['name' => 'Vanesa Typo', 'email' => 'old@example.com', 'phone' => '111']);
    $client->assignRole('client');

    $quote = Quote::factory()->create(['user_id' => $client->id, 'client_name' => 'Vanesa Typo', 'client_email' => 'old@example.com', 'client_phone' => '111']);
    $invoice = Quote::factory()->invoiced()->create(['user_id' => $client->id, 'client_name' => 'Vanesa Typo', 'client_email' => 'old@example.com', 'client_phone' => '111']);

    Volt::actingAs($admin)
        ->test('admin.clients.index')
        ->call('openEdit', $client->id)
        ->set('editName', 'Vanessa Fixed')
        ->set('editEmail', 'new@example.com')
        ->set('editPhone', '222')
        ->call('updateClient')
        ->assertHasNoErrors();

    expect($quote->fresh())
        ->client_name->toBe('Vanessa Fixed')
        ->client_email->toBe('new@example.com')
        ->client_phone->toBe('222')
        ->and($invoice->fresh()->client_name)->toBe('Vanesa Typo');
});
