<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'slug',
        'sort_order',
        'description',
        'is_active',
    ];

    public function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class)->orderBy('sort_order');
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

    /**
     * Categories for the subcategory picker: active subcategories plus any already selected ones,
     * keeping only categories that end up with at least one subcategory.
     *
     * @param  array<int, int|string>  $selectedIds
     * @return Collection<int, Category>
     */
    public static function forSubcategoryPicker(array $selectedIds = []): Collection
    {
        $selectable = fn ($query) => $query->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $selectedIds));

        return static::query()
            ->whereHas('subcategories', $selectable)
            ->with(['subcategories' => $selectable])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function emoji(): string
    {
        return match ($this->slug) {
            'construction' => '🏗️',
            'maintenance' => '🔧',
            'home-cleaning' => '🧹',
            'auto' => '🚗',
            'food' => '🍽️',
            'events' => '🎉',
            'real-estate' => '🏠',
            'beauty' => '💇',
            'health' => '💪',
            'education' => '📚',
            'technology' => '💻',
            'science' => '🔬',
            'art' => '🎨',
            'business' => '💼',
            'pets' => '🐾',
            'transport' => '🚚',
            'care' => '🧸',
            'fashion' => '🧵',
            'sports' => '⚽',
            'agriculture' => '🌾',
            'materials' => '🧱',
            'labor' => '👷',
            'equipment' => '🚜',
            'permits' => '📝',
            default => '🏷️',
        };
    }

    public function canBeDeleted(): bool
    {
        return ! $this->subcategories()->exists() && ! $this->products()->exists();
    }

    public static function uniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'category';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
