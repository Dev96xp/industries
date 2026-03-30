<?php

namespace App\Models;

use Database\Factories\TimeEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    /** @use HasFactory<TimeEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'clock_in_at',
        'clock_out_at',
        'clock_in_lat',
        'clock_in_lng',
        'clock_out_lat',
        'clock_out_lng',
        'clock_in_verified',
        'clock_out_verified',
        'notes',
    ];

    public function casts(): array
    {
        return [
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
            'clock_in_lat' => 'decimal:7',
            'clock_in_lng' => 'decimal:7',
            'clock_out_lat' => 'decimal:7',
            'clock_out_lng' => 'decimal:7',
            'clock_in_verified' => 'boolean',
            'clock_out_verified' => 'boolean',
        ];
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getDurationMinutesAttribute(): ?int
    {
        if (! $this->clock_out_at) {
            return null;
        }

        return (int) $this->clock_in_at->diffInMinutes($this->clock_out_at);
    }

    public function getFormattedDurationAttribute(): string
    {
        $minutes = $this->duration_minutes;

        if ($minutes === null) {
            return 'In progress';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
    }

    /**
     * Check if given coordinates are within the project's geofence.
     * Returns true if project has no GPS configured (no restriction).
     */
    public static function isWithinRadius(Project $project, float $lat, float $lng): bool
    {
        if (! $project->latitude || ! $project->longitude) {
            return true;
        }

        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat - (float) $project->latitude);
        $dLng = deg2rad($lng - (float) $project->longitude);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad((float) $project->latitude))
            * cos(deg2rad($lat))
            * sin($dLng / 2) ** 2;

        $distance = $earthRadius * 2 * asin(sqrt($a));

        return $distance <= $project->geo_radius;
    }
}
