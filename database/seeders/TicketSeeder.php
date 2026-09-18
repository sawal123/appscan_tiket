<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class TicketSeeder extends Seeder
{
    /**
     * Ticket volume per event status.
     *
     * category name => [total tickets, check in every N-th ticket]
     * The second value of 0 means the category has no check-ins yet.
     *
     * @return array<string, array<string, array{0: int, 1: int}>>
     */
    private function plan(): array
    {
        return [
            EventStatus::Active->value => [
                'Regular' => [100, 2],
                'VIP' => [40, 2],
                'VVIP' => [16, 2],
                'Undangan' => [8, 3],
            ],
            EventStatus::Completed->value => [
                'Regular' => [20, 2],
                'VIP' => [10, 2],
            ],
        ];
    }

    public function run(): void
    {
        $scannerIds = User::query()
            ->where('role', UserRole::Scanner->value)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if ($scannerIds === []) {
            throw new RuntimeException('TicketSeeder membutuhkan minimal satu user dengan role scanner.');
        }

        $adminId = User::query()
            ->where('role', UserRole::Admin->value)
            ->orderBy('id')
            ->value('id');

        $plan = $this->plan();
        $scanned = 0;

        foreach (Event::query()->with('ticketCategories')->orderBy('id')->get() as $event) {
            $eventPlan = $plan[$event->status->value] ?? [];

            // Active events are scanned today, finished events on their own event day.
            $scanBase = $event->status === EventStatus::Active
                ? now()
                : $event->event_date->copy()->setTime(19, 0);

            foreach ($event->ticketCategories->sortBy('name') as $category) {
                [$total, $every] = $eventPlan[$category->name] ?? [0, 0];

                if ($total === 0) {
                    continue;
                }

                $prefix = Str::upper(Str::substr(Str::slug($event->name, ''), 0, 4))
                    .'-'.Str::upper(Str::substr(Str::slug($category->name, ''), 0, 3));

                for ($index = 1; $index <= $total; $index++) {
                    $checkedIn = $every > 0 && ($index - 1) % $every === 0;

                    if ($checkedIn) {
                        $scanned++;
                    }

                    $qrCode = sprintf('%s-%04d', $prefix, $index);

                    Ticket::query()->firstOrCreate(
                        ['qr_code' => $qrCode],
                        [
                            'event_id' => $event->id,
                            'ticket_category_id' => $category->id,
                            'code' => $qrCode,
                            'status' => $checkedIn ? Ticket::STATUS_CHECKED_IN : Ticket::STATUS_REGISTERED,
                            'registered_at' => $event->event_date->copy()->subDays(30)->addMinutes($index),
                            'registered_by' => $adminId,
                            'checked_in_at' => $checkedIn ? $scanBase->copy()->subMinutes(2 * $scanned + 1) : null,
                            'checked_in_by' => $checkedIn ? (int) $scannerIds[$scanned % count($scannerIds)] : null,
                        ],
                    );
                }
            }
        }
    }
}
