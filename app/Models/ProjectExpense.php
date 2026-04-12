<?php

namespace App\Models;

use Database\Factories\ProjectExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectExpense extends Model
{
    /** @use HasFactory<ProjectExpenseFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'description',
        'category',
        'amount',
        'expense_date',
        'notes',
        'payment_method',
        'receipt_path',
    ];

    public function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
