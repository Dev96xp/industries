<?php

namespace App\Models;

use Database\Factories\SubcategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Subcategory extends Model
{
    /** @use HasFactory<SubcategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'name_en',
        'slug',
        'sort_order',
        'is_active',
    ];

    public function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * The English name when the app locale is English and one exists, otherwise the default name.
     */
    public function label(): string
    {
        return app()->getLocale() === 'en' && filled($this->name_en)
            ? $this->name_en
            : $this->name;
    }

    public function contractors(): BelongsToMany
    {
        return $this->belongsToMany(Contractor::class)->withTimestamps();
    }

    /**
     * Whether another table references this subcategory, which blocks deleting it.
     */
    public function isInUse(): bool
    {
        return $this->contractors()->exists();
    }

    public static function uniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'subcategory';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
