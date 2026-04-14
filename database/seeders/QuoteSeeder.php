<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuoteSeeder extends Seeder
{
    public function run(): void
    {
        $clients = User::role('client')->orderBy('name')->get();

        if ($clients->isEmpty()) {
            return;
        }

        $products = Product::where('is_active', true)->get()->keyBy('name');

        $quotes = [
            [
                'client' => 'Alejandra Gonzalez',
                'status' => 'accepted',
                'quote_date' => '2026-01-10',
                'expiration_date' => '2026-02-10',
                'tax' => 8.25,
                'notes' => 'Kitchen and bathroom remodel.',
                'terms' => 'Net 30. 50% deposit required before work begins.',
                'items' => [
                    ['name' => 'General Labor',         'qty' => 40,  'unit' => 'hr'],
                    ['name' => 'Ceramic Tile (12x12)',   'qty' => 200, 'unit' => 'sqft'],
                    ['name' => 'Drywall 1/2" (4x8)',     'qty' => 12,  'unit' => 'sheet'],
                    ['name' => 'Paint (Interior, 5 gal)', 'qty' => 3,  'unit' => 'bucket'],
                ],
            ],
            [
                'client' => 'Alejandro Lopez',
                'status' => 'sent',
                'quote_date' => '2026-02-05',
                'expiration_date' => '2026-03-05',
                'tax' => 8.25,
                'notes' => 'Garage addition — concrete slab and framing.',
                'terms' => 'Net 30.',
                'items' => [
                    ['name' => 'Ready-Mix Concrete',    'qty' => 6,   'unit' => 'yd³'],
                    ['name' => 'Rebar #4 (20 ft)',      'qty' => 20,  'unit' => 'each'],
                    ['name' => 'Lumber 2x4x8',          'qty' => 50,  'unit' => 'each'],
                    ['name' => 'Skilled Carpenter',     'qty' => 24,  'unit' => 'hr'],
                    ['name' => 'Concrete Finisher',     'qty' => 8,   'unit' => 'hr'],
                ],
            ],
            [
                'client' => 'Claudia Prueba',
                'status' => 'draft',
                'quote_date' => '2026-03-01',
                'expiration_date' => '2026-04-01',
                'tax' => 0,
                'notes' => 'Roof replacement.',
                'terms' => 'Payment due upon completion.',
                'items' => [
                    ['name' => 'Roofing Shingles',      'qty' => 18,  'unit' => 'square'],
                    ['name' => 'Roofer',                'qty' => 32,  'unit' => 'hr'],
                    ['name' => 'Dump Truck (load)',      'qty' => 2,   'unit' => 'load'],
                ],
            ],
            [
                'client' => 'Fabiola Hernandez Test',
                'status' => 'rejected',
                'quote_date' => '2026-01-20',
                'expiration_date' => '2026-02-20',
                'tax' => 8.25,
                'discount' => 500,
                'notes' => 'Full basement finishing.',
                'terms' => 'Net 15.',
                'items' => [
                    ['name' => 'Drywall 1/2" (4x8)',     'qty' => 30,  'unit' => 'sheet'],
                    ['name' => 'Insulation Batts (R-19)', 'qty' => 10,  'unit' => 'bag'],
                    ['name' => 'Paint (Interior, 5 gal)', 'qty' => 4,  'unit' => 'bucket'],
                    ['name' => 'General Labor',           'qty' => 60,  'unit' => 'hr'],
                    ['name' => 'Electrician',             'qty' => 8,   'unit' => 'hr'],
                ],
            ],
            [
                'client' => 'Ivonne Prueba',
                'status' => 'sent',
                'quote_date' => '2026-03-15',
                'expiration_date' => '2026-04-15',
                'tax' => 8.25,
                'notes' => 'Deck and patio construction.',
                'terms' => 'Net 30. 50% deposit required.',
                'items' => [
                    ['name' => 'Lumber 2x4x8',          'qty' => 80,  'unit' => 'each'],
                    ['name' => 'Concrete Block (8")',    'qty' => 100, 'unit' => 'each'],
                    ['name' => 'Skilled Carpenter',     'qty' => 30,  'unit' => 'hr'],
                    ['name' => 'Site Cleanup',          'qty' => 2,   'unit' => 'day'],
                ],
            ],
            [
                'client' => 'Vanessa Jovanna Prueba',
                'status' => 'draft',
                'quote_date' => '2026-04-01',
                'expiration_date' => '2026-05-01',
                'tax' => 8.25,
                'notes' => 'Plumbing and electrical upgrade.',
                'terms' => 'Net 30.',
                'items' => [
                    ['name' => 'Plumber',                          'qty' => 12, 'unit' => 'hr'],
                    ['name' => 'Electrician',                      'qty' => 16, 'unit' => 'hr'],
                    ['name' => 'PVC Pipe 4" (10 ft)',              'qty' => 8,  'unit' => 'each'],
                    ['name' => 'Electrical Wire 12/2 (250 ft)',   'qty' => 2,  'unit' => 'roll'],
                    ['name' => 'Building Permit',                  'qty' => 1,  'unit' => 'each', 'unit_price' => 350],
                ],
            ],
        ];

        foreach ($quotes as $quoteData) {
            $client = $clients->firstWhere('name', $quoteData['client']);

            if (! $client) {
                continue;
            }

            $quote = Quote::create([
                'user_id' => $client->id,
                'number' => Quote::generateNumber(),
                'client_name' => $client->name,
                'client_email' => $client->email,
                'client_phone' => $client->phone ?? null,
                'quote_date' => $quoteData['quote_date'],
                'expiration_date' => $quoteData['expiration_date'] ?? null,
                'tax_percentage' => $quoteData['tax'] ?? 0,
                'discount' => $quoteData['discount'] ?? 0,
                'notes' => $quoteData['notes'] ?? null,
                'terms' => $quoteData['terms'] ?? null,
                'status' => $quoteData['status'],
            ]);

            foreach ($quoteData['items'] as $i => $item) {
                $product = $products->get($item['name']);
                $unitPrice = $item['unit_price'] ?? ($product?->unit_price ?? 0);

                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $product?->id,
                    'description' => $item['name'],
                    'quantity' => $item['qty'],
                    'unit_price' => $unitPrice,
                    'unit' => $item['unit'],
                    'sort_order' => $i,
                ]);
            }
        }
    }
}
