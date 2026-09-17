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
        return [
            'ticket_category_id' => TicketCategory::factory(),
            'code' => strtoupper(fake()->unique()->bothify('???-####-#####')),
            'checked_in_at' => null,
            'checked_in_by' => null,
        ];
    }

    public function checkedIn(?User $user = null): static
    {
        $checkedInBy = $user?->id;

        return $this->state(fn (array $attributes) => [
            'checked_in_at' => now(),
            'checked_in_by' => $checkedInBy ?? User::factory(),
        ]);
    }
}
