<?php

namespace App\Models;

use Database\Factories\ContractorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Contractor extends Model
{
    /** @use HasFactory<ContractorFactory> */
    use HasFactory;

    protected $fillable = [
        'company_name',
        'contact_name',
        'phone',
        'phone_secondary',
        'email',
        'address',
        'specialty',
        'insurance_company',
        'has_construction_license',
        'license_number',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'has_construction_license' => 'boolean',
        ];
    }

    public function subcategories(): BelongsToMany
    {
        return $this->belongsToMany(Subcategory::class)
            ->withTimestamps()
            ->orderBy('subcategories.sort_order');
    }

    /**
     * Categories derived from the contractor's subcategories.
     *
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return $this->subcategories->pluck('category')->filter()->unique('id')->sortBy('sort_order')->values();
    }
}
