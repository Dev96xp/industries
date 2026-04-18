<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $clients = User::role('client')->orderBy('name')->get()->keyBy('name');
        $locations = Location::orderBy('name')->get()->keyBy('name');

        if ($clients->isEmpty()) {
            return;
        }

        $today = now()->startOfDay();

        $projects = [
            [
                'client' => 'Alejandra Gonzalez',
                'location' => 'Lincoln',
                'name' => 'Kitchen & Bathroom Remodel',
                'description' => 'Full kitchen and bathroom remodel including new tile, drywall, and paint.',
                'status' => 'in_progress',
                'address' => '1420 N 56th St, Lincoln, NE 68503',
                'start_date' => $today->copy()->subDays(5),
                'end_date' => $today->copy()->addDays(30),
                'budget' => 18500.00,
            ],
            [
                'client' => 'Alejandro Lopez',
                'location' => 'Omaha',
                'name' => 'Garage Addition — Slab & Framing',
                'description' => 'Concrete slab pour and wood framing for a detached 2-car garage.',
                'status' => 'planning',
                'address' => '3301 Holdrege St, Lincoln, NE 68503',
                'start_date' => $today->copy()->addDays(7),
                'end_date' => $today->copy()->addDays(45),
                'budget' => 32000.00,
            ],
            [
                'client' => 'Claudia Prueba',
                'location' => 'Omaha',
                'name' => 'Roof Replacement',
                'description' => 'Complete tear-off and replacement of asphalt shingle roof.',
                'status' => 'planning',
                'address' => '620 W O St, Lincoln, NE 68528',
                'start_date' => $today->copy()->addDays(14),
                'end_date' => $today->copy()->addDays(28),
                'budget' => 14200.00,
            ],
            [
                'client' => 'Ivonne Prueba',
                'location' => 'Grand Island',
                'name' => 'Deck & Patio Construction',
                'description' => 'Build a 400 sq ft wood deck with concrete patio extension.',
                'status' => 'in_progress',
                'address' => '2145 S 40th St, Lincoln, NE 68506',
                'start_date' => $today->copy()->subDays(3),
                'end_date' => $today->copy()->addDays(21),
                'budget' => 11800.00,
            ],
            [
                'client' => 'Vanessa Jovanna Prueba',
                'location' => 'Grand Island',
                'name' => 'Plumbing & Electrical Upgrade',
                'description' => 'Full plumbing rough-in and 200-amp electrical panel upgrade.',
                'status' => 'planning',
                'address' => '4890 Pioneers Blvd, Lincoln, NE 68506',
                'start_date' => $today->copy()->addDays(20),
                'end_date' => $today->copy()->addDays(50),
                'budget' => 9400.00,
            ],
            [
                'client' => 'Alejandra Gonzalez',
                'location' => 'Lincoln',
                'name' => 'Master Bedroom Addition',
                'description' => 'New 300 sq ft master bedroom addition with walk-in closet.',
                'status' => 'planning',
                'address' => '1420 N 56th St, Lincoln, NE 68503',
                'start_date' => $today->copy()->addDays(35),
                'end_date' => $today->copy()->addDays(70),
                'budget' => 47000.00,
            ],
            [
                'client' => 'Alejandro Lopez',
                'location' => 'Omaha',
                'name' => 'Basement Waterproofing',
                'description' => 'Interior drainage system installation and sump pump replacement.',
                'status' => 'in_progress',
                'address' => '3301 Holdrege St, Lincoln, NE 68503',
                'start_date' => $today->copy()->subDays(8),
                'end_date' => $today->copy()->addDays(10),
                'budget' => 6800.00,
            ],
            [
                'client' => 'Claudia Prueba',
                'location' => 'Omaha',
                'name' => 'Driveway Concrete Replacement',
                'description' => 'Remove old asphalt driveway and pour new 4" concrete slab.',
                'status' => 'on_hold',
                'address' => '620 W O St, Lincoln, NE 68528',
                'start_date' => $today->copy()->addDays(25),
                'end_date' => $today->copy()->addDays(40),
                'budget' => 7200.00,
            ],
            [
                'client' => 'Ivonne Prueba',
                'location' => 'Grand Island',
                'name' => 'Fence Installation — Backyard',
                'description' => 'Install 180 linear feet of cedar privacy fence with two gates.',
                'status' => 'planning',
                'address' => '2145 S 40th St, Lincoln, NE 68506',
                'start_date' => $today->copy()->addDays(42),
                'end_date' => $today->copy()->addDays(55),
                'budget' => 8900.00,
            ],
            [
                'client' => 'Vanessa Jovanna Prueba',
                'location' => 'Grand Island',
                'name' => 'Window Replacement — Full Home',
                'description' => 'Replace 14 double-hung windows with energy-efficient vinyl units.',
                'status' => 'planning',
                'address' => '4890 Pioneers Blvd, Lincoln, NE 68506',
                'start_date' => $today->copy()->addDays(50),
                'end_date' => $today->copy()->addDays(60),
                'budget' => 15600.00,
            ],
        ];

        foreach ($projects as $data) {
            $client = $clients->get($data['client']);

            if (! $client) {
                continue;
            }

            Project::create([
                'number' => Project::generateNumber(),
                'name' => $data['name'],
                'description' => $data['description'],
                'status' => $data['status'],
                'address' => $data['address'],
                'start_date' => $data['start_date'],
                'estimated_completion_date' => $data['end_date'],
                'budget' => $data['budget'],
                'client_user_id' => $client->id,
                'location_id' => $locations->get($data['location'])?->id,
                'is_featured' => false,
                'is_archived' => false,
            ]);
        }
    }
}
