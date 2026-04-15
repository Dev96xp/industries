<?php

namespace App\Models;

use Database\Factories\QuotePaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotePayment extends Model
{
    /** @use HasFactory<QuotePaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'amount',
        'paid_at',
        'method',
        'status',
        'notes',
        'stripe_payment_link',
        'stripe_session_id',
    ];

    public function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'cash' => 'Cash',
            'check' => 'Check',
            'transfer' => 'Bank Transfer',
            'card' => 'Card (Stripe)',
            default => ucfirst($this->method),
        };
    }
}
