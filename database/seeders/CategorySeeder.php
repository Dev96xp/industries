<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Materials',      'description' => 'Raw materials and supplies used in construction.'],
            ['name' => 'Labor',          'description' => 'Labor costs including skilled and general workers.'],
            ['name' => 'Equipment',      'description' => 'Equipment rentals and machinery costs.'],
            ['name' => 'Permits',        'description' => 'Government permits, inspections and fees.'],
            ['name' => 'Other',          'description' => 'Miscellaneous services and costs.'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], array_merge($category, ['is_active' => true]));
        }
    }
}
