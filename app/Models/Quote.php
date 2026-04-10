<?php

namespace App\Models;

use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    /** @use HasFactory<QuoteFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'number',
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
            'tax_percentage' => 'decimal:2',
            'discount' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
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
}
