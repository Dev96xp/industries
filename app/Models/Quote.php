<?php

namespace App\Models;

use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stripe\StripeClient;

class Quote extends Model
{
    /** @use HasFactory<QuoteFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'number',
        'title',
        'job_address',
        'invoice_number',
        'invoice_date',
        'invoice_due_date',
        'client_name',
        'client_email',
        'client_phone',
        'quote_date',
        'expiration_date',
        'tax_percentage',
        'discount',
        'notes',
        'terms',
        'status',
        'project_id',
    ];

    public function casts(): array
    {
        return [
            'quote_date' => 'date',
            'expiration_date' => 'date',
            'invoice_date' => 'date',
            'invoice_due_date' => 'date',
            'tax_percentage' => 'decimal:2',
            'discount' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(QuotePayment::class)->orderBy('paid_at');
    }

    public function completedPayments(): HasMany
    {
        return $this->hasMany(QuotePayment::class)->where('status', 'completed')->orderBy('paid_at');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum(fn (QuoteItem $item) => $item->quantity * $item->unit_price);
    }

    public function getTaxAmountAttribute(): float
    {
        return round($this->subtotal * ($this->tax_percentage / 100), 2);
    }

    public function getTotalAttribute(): float
    {
        return round($this->subtotal + $this->tax_amount - (float) $this->discount, 2);
    }

    public function getAmountPaidAttribute(): float
    {
        return (float) $this->payments->where('status', 'completed')->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return round($this->total - $this->amount_paid, 2);
    }

    public static function generateNumber(): string
    {
        $last = static::orderByDesc('id')->value('number');

        if (! $last) {
            return 'Q-0001';
        }

        $n = (int) substr($last, 2);

        return 'Q-'.str_pad($n + 1, 4, '0', STR_PAD_LEFT);
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', 'accepted');
    }

    public function scopeInvoices(Builder $query): Builder
    {
        return $query->whereNotNull('invoice_number');
    }

    public function scopeNotInvoiced(Builder $query): Builder
    {
        return $query->whereNull('invoice_number');
    }

    public function isInvoice(): bool
    {
        return $this->invoice_number !== null;
    }

    public function canBeInvoiced(): bool
    {
        return $this->status === 'accepted' && ! $this->isInvoice();
    }

    public function canRevertToQuote(): bool
    {
        return $this->isInvoice() && ! $this->payments()->exists();
    }

    public function getDocumentLabelAttribute(): string
    {
        return $this->isInvoice() ? 'Invoice' : 'Quote';
    }

    public function getDisplayNumberAttribute(): string
    {
        return $this->invoice_number ?? $this->number;
    }

    /**
     * @return 'paid'|'partial'|'unpaid'
     */
    public function getPaymentStatusAttribute(): string
    {
        $amountPaid = $this->amount_paid;

        if ($amountPaid > 0 && $amountPaid >= $this->total) {
            return 'paid';
        }

        return $amountPaid > 0 ? 'partial' : 'unpaid';
    }

    public static function generateInvoiceNumber(): string
    {
        $last = static::withTrashed()->whereNotNull('invoice_number')->orderByDesc('invoice_number')->value('invoice_number');

        if (! $last) {
            return 'INV-0001';
        }

        $n = (int) substr($last, 4);

        return 'INV-'.str_pad($n + 1, 4, '0', STR_PAD_LEFT);
    }

    public function convertToInvoice(string $dueDate): void
    {
        $this->update([
            'invoice_number' => static::generateInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'invoice_due_date' => $dueDate,
        ]);
    }

    public function revertToQuote(): void
    {
        $this->update([
            'invoice_number' => null,
            'invoice_date' => null,
            'invoice_due_date' => null,
        ]);
    }

    /**
     * Creates a Stripe Payment Link for the given amount and records it as a pending payment.
     */
    public function createStripePaymentLink(float $amount): QuotePayment
    {
        $amount = round($amount, 2);
        $stripe = new StripeClient(config('services.stripe.secret'));

        $paymentLink = $stripe->paymentLinks->create([
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $this->document_label.' '.$this->display_number.' — '.$this->client_name,
                    ],
                    'unit_amount' => (int) round($amount * 100),
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'quote_id' => $this->id,
                'quote_number' => $this->number,
            ],
        ]);

        return $this->payments()->create([
            'amount' => $amount,
            'paid_at' => now()->format('Y-m-d'),
            'method' => 'card',
            'status' => 'pending',
            'notes' => 'Stripe Payment Link — awaiting payment',
            'stripe_payment_link' => $paymentLink->url,
            'stripe_session_id' => $paymentLink->id,
        ]);
    }

    /**
     * Returns an existing pending Stripe Payment Link that matches the current balance due.
     */
    public function pendingPaymentLinkForBalance(): ?string
    {
        $this->loadMissing('payments', 'items');
        $balanceDue = $this->balance_due;

        return $this->payments
            ->where('status', 'pending')
            ->whereNotNull('stripe_payment_link')
            ->first(fn (QuotePayment $payment) => round((float) $payment->amount, 2) === $balanceDue)
            ?->stripe_payment_link;
    }

    /**
     * Returns a Stripe Payment Link for the current balance due, reusing a pending one for the same amount.
     */
    public function paymentLinkForBalance(): ?string
    {
        $this->load('payments', 'items');
        $balanceDue = $this->balance_due;

        if ($balanceDue < 0.50 || ! config('services.stripe.secret')) {
            return null;
        }

        if ($existing = $this->pendingPaymentLinkForBalance()) {
            return $existing;
        }

        try {
            return $this->createStripePaymentLink($balanceDue)->stripe_payment_link;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
