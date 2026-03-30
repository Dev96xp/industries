<?php

namespace App\Models;

use Database\Factories\ProjectIncomeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectIncome extends Model
{
    /** @use HasFactory<ProjectIncomeFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'description',
        'source',
        'amount',
        'income_date',
        'notes',
        'payment_method',
    ];

    public function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'income_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
