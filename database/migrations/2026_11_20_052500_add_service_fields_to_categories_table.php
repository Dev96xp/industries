<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Adds service-category fields to the existing categories table without
 * touching existing rows: every current category keeps its id, name,
 * description and products, and only receives a generated slug and its
 * current position (by id) as sort_order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
            $table->string('slug')->nullable()->after('name_en');
            $table->unsignedInteger('sort_order')->default(0)->after('slug');
        });

        $usedSlugs = [];

        foreach (DB::table('categories')->orderBy('id')->get(['id', 'name']) as $position => $category) {
            $base = Str::slug($category->name) ?: 'category';
            $slug = $base;
            $suffix = 2;

            while (in_array($slug, $usedSlugs, true)) {
                $slug = "{$base}-{$suffix}";
                $suffix++;
            }

            $usedSlugs[] = $slug;

            DB::table('categories')->where('id', $category->id)->update([
                'slug' => $slug,
                'sort_order' => $position,
            ]);
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['name_en', 'slug', 'sort_order']);
        });
    }
};
