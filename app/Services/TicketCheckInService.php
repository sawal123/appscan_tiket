<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TicketCheckInService
{
    public const SUCCESS = 'success';

    public function __construct(
        private readonly TicketValidationService $validation,
    ) {}

    /**
     * Check a ticket in, guarding against double scans and race conditions.
     *
     * @return array<string, mixed>
     */
    public function checkIn(string $code, ?User $user = null): array
    {
        $normalized = $this->validation->normalize($code);

        return DB::transaction(function () use ($normalized, $user): array {
            $ticket = Ticket::query()
                ->forActiveEvent()
                ->with(['ticketCategory.event'])
                ->where(function ($query) use ($normalized): void {
                    $query->where('qr_code', $normalized)->orWhere('code', $normalized);
                })
                ->lockForUpdate()
                ->first();

            if (! $ticket) {
                return $this->validation->payload(TicketValidationService::NOT_FOUND, $normalized);
            }

            if ($ticket->checked_in_at !== null) {
                return $this->validation->payload(TicketValidationService::USED, $normalized, $ticket);
            }

            $ticket->forceFill([
                'status' => Ticket::STATUS_CHECKED_IN,
                'checked_in_at' => now(),
                'checked_in_by' => $user?->id,
            ])->save();

            return $this->validation->payload(self::SUCCESS, $normalized, $ticket, $user);
        });
    }
}
