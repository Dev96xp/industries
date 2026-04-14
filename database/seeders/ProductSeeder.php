<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'name')->map(fn ($id) => $id)->toArray();

        $products = [
            // Materials
            ['name' => 'Concrete Block (8")',            'category' => 'Materials', 'unit' => 'each',   'unit_price' => 2.50],
            ['name' => 'Ready-Mix Concrete',             'category' => 'Materials', 'unit' => 'yd³',    'unit_price' => 165.00],
            ['name' => 'Rebar #4 (20 ft)',               'category' => 'Materials', 'unit' => 'each',   'unit_price' => 12.00],
            ['name' => 'Lumber 2x4x8',                   'category' => 'Materials', 'unit' => 'each',   'unit_price' => 6.50],
            ['name' => 'Plywood 3/4" (4x8)',             'category' => 'Materials', 'unit' => 'sheet',  'unit_price' => 55.00],
            ['name' => 'Drywall 1/2" (4x8)',             'category' => 'Materials', 'unit' => 'sheet',  'unit_price' => 18.00],
            ['name' => 'Roofing Shingles',               'category' => 'Materials', 'unit' => 'square', 'unit_price' => 120.00],
            ['name' => 'Insulation Batts (R-19)',        'category' => 'Materials', 'unit' => 'bag',    'unit_price' => 45.00],
            ['name' => 'PVC Pipe 4" (10 ft)',            'category' => 'Materials', 'unit' => 'each',   'unit_price' => 22.00],
            ['name' => 'Electrical Wire 12/2 (250 ft)', 'category' => 'Materials', 'unit' => 'roll',   'unit_price' => 95.00],
            ['name' => 'Paint (Interior, 5 gal)',        'category' => 'Materials', 'unit' => 'bucket', 'unit_price' => 85.00],
            ['name' => 'Ceramic Tile (12x12)',           'category' => 'Materials', 'unit' => 'sqft',   'unit_price' => 4.50],
            ['name' => 'Sand (ton)',                     'category' => 'Materials', 'unit' => 'ton',    'unit_price' => 40.00],
            ['name' => 'Gravel (ton)',                   'category' => 'Materials', 'unit' => 'ton',    'unit_price' => 45.00],
            ['name' => 'Mortar Mix (60 lb)',             'category' => 'Materials', 'unit' => 'bag',    'unit_price' => 9.00],

            // Labor
            ['name' => 'General Labor',        'category' => 'Labor', 'unit' => 'hr',   'unit_price' => 35.00],
            ['name' => 'Skilled Carpenter',    'category' => 'Labor', 'unit' => 'hr',   'unit_price' => 65.00],
            ['name' => 'Electrician',          'category' => 'Labor', 'unit' => 'hr',   'unit_price' => 85.00],
            ['name' => 'Plumber',              'category' => 'Labor', 'unit' => 'hr',   'unit_price' => 90.00],
            ['name' => 'Mason / Block Layer',  'category' => 'Labor', 'unit' => 'hr',   'unit_price' => 70.00],
            ['name' => 'Painter',              'category' => 'Labor', 'unit' => 'hr',   'unit_price' => 50.00],
            ['name' => 'Roofer',               'category' => 'Labor', 'unit' => 'hr',   'unit_price' => 75.00],
            ['name' => 'Concrete Finisher',    'category' => 'Labor', 'unit' => 'hr',   'unit_price' => 60.00],
            ['name' => 'Project Supervisor',   'category' => 'Labor', 'unit' => 'day',  'unit_price' => 450.00],
            ['name' => 'Drywall Installer',    'category' => 'Labor', 'unit' => 'sqft', 'unit_price' => 2.50],

            // Equipment
            ['name' => 'Excavator Rental',           'category' => 'Equipment', 'unit' => 'day',  'unit_price' => 850.00],
            ['name' => 'Concrete Mixer Rental',      'category' => 'Equipment', 'unit' => 'day',  'unit_price' => 150.00],
            ['name' => 'Scaffolding (per section)',  'category' => 'Equipment', 'unit' => 'week', 'unit_price' => 75.00],
            ['name' => 'Generator Rental',           'category' => 'Equipment', 'unit' => 'day',  'unit_price' => 200.00],
            ['name' => 'Forklift Rental',            'category' => 'Equipment', 'unit' => 'day',  'unit_price' => 350.00],
            ['name' => 'Dump Truck (load)',          'category' => 'Equipment', 'unit' => 'load', 'unit_price' => 300.00],

            // Permits
            ['name' => 'Building Permit',   'category' => 'Permits', 'unit' => 'each', 'unit_price' => null],
            ['name' => 'Electrical Permit', 'category' => 'Permits', 'unit' => 'each', 'unit_price' => null],
            ['name' => 'Plumbing Permit',   'category' => 'Permits', 'unit' => 'each', 'unit_price' => null],
            ['name' => 'Inspection Fee',    'category' => 'Permits', 'unit' => 'each', 'unit_price' => null],

            // Other
            ['name' => 'Site Cleanup',             'category' => 'Other', 'unit' => 'day',  'unit_price' => 250.00],
            ['name' => 'Waste Disposal / Dumpster', 'category' => 'Other', 'unit' => 'week', 'unit_price' => 400.00],
            ['name' => 'Design / Drafting',        'category' => 'Other', 'unit' => 'hr',   'unit_price' => 95.00],
            ['name' => 'Project Management Fee',   'category' => 'Other', 'unit' => '%',    'unit_price' => null],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['name' => $product['name']],
                [
                    'category_id' => $categories[$product['category']] ?? null,
                    'unit' => $product['unit'],
                    'unit_price' => $product['unit_price'],
                    'is_active' => true,
                ]
            );
        }
    }
}
