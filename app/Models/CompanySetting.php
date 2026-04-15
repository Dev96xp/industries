<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_name',
        'tagline',
        'address',
        'city',
        'state',
        'zip',
        'phone',
        'phone_secondary',
        'email',
        'license_number',
        'founded_year',
        'facebook',
        'instagram',
        'linkedin',
        'livewire_max_payload_mb',
        'ip_restriction_enabled',
        'allowed_ips',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate([], ['company_name' => 'Nucleus Industries']);
    }
}
