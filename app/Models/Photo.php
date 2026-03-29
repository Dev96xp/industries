<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    /** @use HasFactory<\Database\Factories\PhotoFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'path',
        'disk',
        'mime_type',
        'size',
        'category',
        'is_featured',
        'is_hero',
        'is_about',
        'sort_order',
    ];

    public function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_hero'     => 'boolean',
            'is_about'    => 'boolean',
        ];
    }

    public function url(): string
    {
        return \Storage::disk($this->disk)->url($this->path);
    }

    public function scopeFeatured(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_featured', true)->orderBy('sort_order');
    }

    public function scopeHero(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_hero', true);
    }

    public function scopeAbout(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_about', true);
    }
}
