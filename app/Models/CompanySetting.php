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
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], ['company_name' => 'Nucleus Industries']);
    }
}
