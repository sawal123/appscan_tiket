<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->bothify('???-####-#####'));

        return [
            'event_id' => null,
            'ticket_category_id' => TicketCategory::factory(),
            'code' => $code,
            'qr_code' => null,
            'status' => Ticket::STATUS_REGISTERED,
            'registered_at' => now(),
            'registered_by' => null,
            'checked_in_at' => null,
            'checked_in_by' => null,
        ];
    }

    public function checkedIn(?User $user = null): static
    {
        $checkedInBy = $user?->id;

        return $this->state(fn (array $attributes) => [
            'status' => Ticket::STATUS_CHECKED_IN,
            'checked_in_at' => now(),
            'checked_in_by' => $checkedInBy ?? User::factory(),
        ]);
    }
}
