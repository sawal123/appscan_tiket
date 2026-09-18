<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    /**
     * @var array<string, array<int, string>>
     */
    private const CATEGORIES = [
        'active' => ['Regular', 'VIP', 'VVIP', 'Undangan'],
        'completed' => ['Regular', 'VIP'],
        'draft' => ['Regular', 'VIP'],
    ];

    public function run(): void
    {
        foreach (Event::query()->orderBy('id')->get() as $event) {
            $names = self::CATEGORIES[$event->status->value];

            foreach ($names as $name) {
                TicketCategory::query()->firstOrCreate(
                    ['event_id' => $event->id, 'name' => $name],
                    ['is_active' => true],
                );
            }
        }
    }
}
