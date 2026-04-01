<?php

namespace App\Models;

use Database\Factories\ContractorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
