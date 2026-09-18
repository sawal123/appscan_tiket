<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'name' => 'Festival ABC 2026',
                'event_date' => now()->toDateString(),
                'location' => 'Medan',
                'status' => EventStatus::Active,
            ],
            [
                'name' => 'Pameran Teknologi 2025',
                'event_date' => now()->subMonths(3)->toDateString(),
                'location' => 'Surabaya',
                'status' => EventStatus::Completed,
            ],
            [
                'name' => 'Konser Nusantara 2026',
                'event_date' => now()->addMonths(2)->toDateString(),
                'location' => 'Jakarta',
                'status' => EventStatus::Draft,
            ],
        ];

        foreach ($events as $event) {
            Event::query()->firstOrCreate(['name' => $event['name']], $event);
        }
    }
}
