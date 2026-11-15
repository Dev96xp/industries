<?php

use App\Models\Quote;
use App\Models\User;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'client']);
    collect(['manage quotes', 'manage clients'])->each(fn (string $name) => Permission::firstOrCreate(['name' => $name]));

    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo(['manage quotes', 'manage clients']);

    $this->client = User::factory()->create(['name' => 'Archie Client']);
    $this->client->assignRole('client');
});

it('archives a client without touching their quotes', function () {
    $quote = Quote::factory()->create(['user_id' => $this->client->id, 'title' => 'Old roofing job']);

    Volt::actingAs($this->admin)
        ->test('admin.clients.index')
        ->assertSee('Archie Client')
        ->call('archiveClient', $this->client->id)
        ->assertViewHas('clients', fn ($clients) => ! $clients->contains('id', $this->client->id))
        ->set('statusTab', 'archived')
        ->assertViewHas('clients', fn ($clients) => $clients->contains('id', $this->client->id));

    expect($this->client->fresh()->isArchived())->toBeTrue()
        ->and($quote->fresh()->user_id)->toBe($this->client->id);

    Volt::actingAs($this->admin)
        ->test('admin.quotes.index')
        ->assertSee('Old roofing job');
});

it('restores an archived client', function () {
    $this->client->forceFill(['archived_at' => now()])->save();

    Volt::actingAs($this->admin)
        ->test('admin.clients.index')
        ->set('statusTab', 'archived')
        ->call('restoreClient', $this->client->id)
        ->set('statusTab', 'active')
        ->assertSee('Archie Client');

    expect($this->client->fresh()->isArchived())->toBeFalse();
});

it('hides archived clients from the new quote client selector', function () {
    $active = User::factory()->create(['name' => 'Active Annie']);
    $active->assignRole('client');
    $this->client->forceFill(['archived_at' => now()])->save();

    Volt::actingAs($this->admin)
        ->test('admin.quotes.create')
        ->assertSee('Active Annie')
        ->assertDontSee('Archie Client');
});

it('lets archived clients keep logging in to their account', function () {
    $this->client->forceFill(['archived_at' => now()])->save();

    $this->actingAs($this->client)
        ->get(route('client.account'))
        ->assertOk();
});

it('shows active and archived counts on the tabs', function () {
    $other = User::factory()->create();
    $other->assignRole('client');
    $this->client->forceFill(['archived_at' => now()])->save();

    Volt::actingAs($this->admin)
        ->test('admin.clients.index')
        ->assertViewHas('activeCount', 1)
        ->assertViewHas('archivedCount', 1);
});
