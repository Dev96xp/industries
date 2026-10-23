<?php

use App\Mail\InvoiceSent;
use App\Models\CompanySetting;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\QuotePayment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'client']);
    Permission::firstOrCreate(['name' => 'manage quotes']);

    config(['services.stripe.secret' => null]);

    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo('manage quotes');
});

it('converts an accepted quote to an invoice with a due date', function () {
    $quote = Quote::factory()->accepted()->create();
    $dueDate = now()->addDays(30)->format('Y-m-d');

    Volt::actingAs($this->admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->call('openInvoiceModal')
        ->assertSet('invoiceDueDate', now()->addDays(15)->format('Y-m-d'))
        ->set('invoiceDueDate', $dueDate)
        ->call('convertToInvoice')
        ->assertHasNoErrors()
        ->assertSet('showInvoiceModal', false);

    $quote->refresh();

    expect($quote->isInvoice())->toBeTrue()
        ->and($quote->invoice_number)->toBe('INV-0001')
        ->and($quote->invoice_date->toDateString())->toBe(now()->toDateString())
        ->and($quote->invoice_due_date->toDateString())->toBe($dueDate);
});

it('does not convert a quote that has not been accepted', function (string $status) {
    $quote = Quote::factory()->create(['status' => $status]);

    Volt::actingAs($this->admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->set('invoiceDueDate', now()->addDays(15)->format('Y-m-d'))
        ->call('convertToInvoice');

    expect($quote->fresh()->isInvoice())->toBeFalse();
})->with(['draft', 'sent', 'rejected']);

it('requires a due date that is not in the past', function () {
    $quote = Quote::factory()->accepted()->create();

    Volt::actingAs($this->admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->set('invoiceDueDate', now()->subDay()->format('Y-m-d'))
        ->call('convertToInvoice')
        ->assertHasErrors(['invoiceDueDate' => 'after_or_equal']);

    expect($quote->fresh()->isInvoice())->toBeFalse();
});

it('generates sequential invoice numbers including archived invoices', function () {
    Quote::factory()->invoiced()->create(['invoice_number' => 'INV-0007'])->delete();

    expect(Quote::generateInvoiceNumber())->toBe('INV-0008');
});

it('locks line items once the quote is an invoice', function () {
    $quote = Quote::factory()->invoiced()->create(['title' => 'Roofing']);
    QuoteItem::factory()->create(['quote_id' => $quote->id, 'description' => 'Shingles']);

    Volt::actingAs($this->admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->set('title', 'Changed')
        ->call('save')
        ->call('addItem')
        ->assertCount('items', 1);

    expect($quote->fresh()->title)->toBe('Roofing');
});

it('reverts an invoice without payments back to a quote', function () {
    $quote = Quote::factory()->invoiced()->create();

    Volt::actingAs($this->admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->call('revertToQuote');

    $quote->refresh();

    expect($quote->isInvoice())->toBeFalse()
        ->and($quote->invoice_date)->toBeNull()
        ->and($quote->invoice_due_date)->toBeNull()
        ->and($quote->status)->toBe('accepted');
});

it('does not revert an invoice that has payments', function () {
    $quote = Quote::factory()->invoiced()->create();
    QuotePayment::factory()->create(['quote_id' => $quote->id, 'amount' => 100]);

    Volt::actingAs($this->admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->call('revertToQuote');

    expect($quote->fresh()->isInvoice())->toBeTrue();
});

it('sends the invoice email from the edit page', function () {
    Mail::fake();

    $quote = Quote::factory()->invoiced()->create(['client_email' => 'client@example.com']);
    QuoteItem::factory()->create(['quote_id' => $quote->id, 'quantity' => 1, 'unit_price' => 500]);

    Volt::actingAs($this->admin)
        ->test('admin.quotes.edit', ['quote' => $quote])
        ->call('sendInvoice');

    Mail::assertSent(InvoiceSent::class, fn (InvoiceSent $mail) => $mail->hasTo('client@example.com')
        && $mail->quote->is($quote)
        && $mail->payUrl === null);
});

it('sends the invoice email from the index', function () {
    Mail::fake();

    $quote = Quote::factory()->invoiced()->create(['client_email' => 'client@example.com']);

    Volt::actingAs($this->admin)
        ->test('admin.quotes.index')
        ->call('sendInvoice', $quote->id);

    Mail::assertSent(InvoiceSent::class, fn (InvoiceSent $mail) => $mail->hasTo('client@example.com'));
});

it('reuses a pending stripe link that matches the balance due', function () {
    $quote = Quote::factory()->invoiced()->create(['tax_percentage' => 0, 'discount' => 0]);
    QuoteItem::factory()->create(['quote_id' => $quote->id, 'quantity' => 1, 'unit_price' => 500]);
    QuotePayment::factory()->create([
        'quote_id' => $quote->id,
        'amount' => 500,
        'method' => 'card',
        'status' => 'pending',
        'stripe_payment_link' => 'https://buy.stripe.com/test_link',
    ]);

    expect($quote->pendingPaymentLinkForBalance())->toBe('https://buy.stripe.com/test_link');
});

it('renders the invoice email with the balance due', function () {
    $quote = Quote::factory()->invoiced()->create(['invoice_number' => 'INV-0042', 'tax_percentage' => 0, 'discount' => 0]);
    QuoteItem::factory()->create(['quote_id' => $quote->id, 'quantity' => 1, 'unit_price' => 750]);

    $mail = new InvoiceSent($quote->load('items'), CompanySetting::current(), 'https://buy.stripe.com/test_link');

    $mail->assertHasSubject('Invoice INV-0042 — '.CompanySetting::current()->company_name);
    $mail->assertSeeInHtml('INV-0042');
    $mail->assertSeeInHtml('$750.00');
    $mail->assertSeeInHtml('https://buy.stripe.com/test_link');
    $mail->assertDontSeeInHtml('Approve Quote');
});

it('separates quotes and invoices into tabs', function () {
    Quote::factory()->create(['number' => 'Q-0201']);
    Quote::factory()->invoiced()->create(['number' => 'Q-0202', 'invoice_number' => 'INV-0201']);

    Volt::actingAs($this->admin)
        ->test('admin.quotes.index')
        ->assertSee('Q-0201')
        ->assertDontSee('INV-0201')
        ->set('activeTab', 'invoices')
        ->assertSee('INV-0201')
        ->assertDontSee('Q-0201');
});

it('calculates the invoice payment status', function () {
    $quote = Quote::factory()->invoiced()->create(['tax_percentage' => 0, 'discount' => 0]);
    QuoteItem::factory()->create(['quote_id' => $quote->id, 'quantity' => 1, 'unit_price' => 1000]);

    expect($quote->fresh()->payment_status)->toBe('unpaid');

    QuotePayment::factory()->create(['quote_id' => $quote->id, 'amount' => 400, 'status' => 'completed']);
    expect($quote->fresh()->payment_status)->toBe('partial');

    QuotePayment::factory()->create(['quote_id' => $quote->id, 'amount' => 600, 'status' => 'completed']);
    expect($quote->fresh()->payment_status)->toBe('paid');
});

it('shows the invoice on the admin report and client view', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $quote = Quote::factory()->invoiced()->create([
        'user_id' => $client->id,
        'number' => 'Q-0301',
        'invoice_number' => 'INV-0301',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.quotes.report', $quote))
        ->assertOk()
        ->assertSee('INVOICE')
        ->assertSee('INV-0301')
        ->assertSee('Ref. Quote Q-0301')
        ->assertSee('Balance Due');

    $this->actingAs($client)
        ->get(route('client.quotes.view', $quote))
        ->assertOk()
        ->assertSee('INV-0301')
        ->assertSee('Balance Due');
});
