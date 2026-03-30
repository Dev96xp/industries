<?php

namespace App\Models;

use Database\Factories\ProjectPhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectPhoto extends Model
{
    /** @use HasFactory<ProjectPhotoFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'path',
        'disk',
        'caption',
        'sort_order',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
