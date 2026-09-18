<?php

namespace App\Services;

use App\Models\CheckInLog;
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
                $this->logAttempt(null, $user, $normalized, CheckInLog::STATUS_INVALID);

                return $this->validation->payload(TicketValidationService::NOT_FOUND, $normalized);
            }

            if ($ticket->checked_in_at !== null) {
                $this->logAttempt($ticket, $user, $normalized, CheckInLog::STATUS_ALREADY_CHECKED_IN);

                return $this->validation->payload(TicketValidationService::USED, $normalized, $ticket);
            }

            $ticket->forceFill([
                'status' => Ticket::STATUS_CHECKED_IN,
                'checked_in_at' => now(),
                'checked_in_by' => $user?->id,
            ])->save();

            $this->logAttempt($ticket, $user, $normalized, CheckInLog::STATUS_SUCCESS);

            return $this->validation->payload(self::SUCCESS, $normalized, $ticket, $user);
        });
    }

    private function logAttempt(?Ticket $ticket, ?User $user, string $code, string $status): void
    {
        CheckInLog::create([
            'ticket_id' => $ticket?->id,
            'scanner_id' => $user?->id,
            'qr_code' => $ticket instanceof Ticket ? $ticket->qr_code : $code,
            'status' => $status,
            'scanned_at' => now(),
        ]);
    }
}
