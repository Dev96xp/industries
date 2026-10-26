<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        'backup_enabled',
        'backup_frequency',
        'backup_day_of_month',
        'backup_day',
        'backup_time',
        'backup_email',
        'website_video_path',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'backup_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate([], ['company_name' => 'Nucleus Industries']);
    }

    public function websiteVideoUrl(): ?string
    {
        return $this->website_video_path
            ? Storage::disk('public')->url($this->website_video_path)
            : null;
    }
}
