<?php

use App\Models\Quote;
use App\Models\QuotePayment;
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
});

it('admin can record a cash payment on a quote', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage quotes');

    $quote = Quote::factory()->create(['status' => 'accepted']);

    Volt::actingAs($admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->call('openPaymentModal')
        ->set('paymentAmount', '500.00')
        ->set('paymentDate', '2026-04-15')
        ->set('paymentMethod', 'cash')
        ->set('paymentNotes', 'First installment')
        ->call('recordPayment');

    $payment = QuotePayment::where('quote_id', $quote->id)->first();

    expect($payment)->not->toBeNull()
        ->and((float) $payment->amount)->toBe(500.0)
        ->and($payment->method)->toBe('cash')
        ->and($payment->notes)->toBe('First installment');
});

it('payment requires a positive amount', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage quotes');

    $quote = Quote::factory()->create();

    Volt::actingAs($admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->call('openPaymentModal')
        ->set('paymentAmount', '0')
        ->set('paymentDate', '2026-04-15')
        ->set('paymentMethod', 'cash')
        ->call('recordPayment')
        ->assertHasErrors(['paymentAmount']);
});

it('admin can delete a payment record', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage quotes');

    $quote = Quote::factory()->create(['status' => 'accepted']);
    $payment = QuotePayment::factory()->create(['quote_id' => $quote->id, 'amount' => 250.00]);

    Volt::actingAs($admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->call('deletePayment', $payment->id);

    expect(QuotePayment::find($payment->id))->toBeNull();
});

it('cannot modify a fully paid quote', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage quotes');

    $quote = Quote::factory()->create(['status' => 'accepted', 'tax_percentage' => 0, 'discount' => 0]);
    // Pay exactly the total (use total from the factory — we just need paid >= total)
    QuotePayment::factory()->create([
        'quote_id' => $quote->id,
        'amount' => $quote->total ?: 1000,
        'method' => 'cash',
    ]);

    $originalStatus = $quote->status;

    Volt::actingAs($admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->set('status', 'draft')
        ->call('save');

    // Status must not have changed
    expect($quote->fresh()->status)->toBe($originalStatus);
});

it('balance due reflects payments made', function () {
    $quote = Quote::factory()->create(['tax_percentage' => 0, 'discount' => 0]);

    QuotePayment::factory()->create(['quote_id' => $quote->id, 'amount' => 200]);
    QuotePayment::factory()->create(['quote_id' => $quote->id, 'amount' => 300]);

    $quote->load('payments');

    expect($quote->amount_paid)->toBe(500.0);
});
