<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'name',
        'description',
        'status',
        'address',
        'start_date',
        'estimated_completion_date',
        'budget',
        'client_user_id',
        'internal_notes',
        'is_featured',
        'is_archived',
        'latitude',
        'longitude',
        'geo_radius',
    ];

    public function casts(): array
    {
        return [
            'start_date' => 'date',
            'estimated_completion_date' => 'date',
            'budget' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProjectPhoto::class)->orderBy('sort_order');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class)->orderByDesc('expense_date');
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(ProjectIncome::class)->orderByDesc('income_date');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class)->orderByDesc('clock_in_at');
    }

    public static function generateNumber(): string
    {
        $last = static::orderByDesc('id')->value('number');

        if (! $last) {
            return 'P-0001';
        }

        $n = (int) substr($last, 2);

        return 'P-'.str_pad($n + 1, 4, '0', STR_PAD_LEFT);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }
}
