<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Str;

class TicketValidationService
{
    public const NOT_FOUND = 'not_found';

    public const VALID = 'valid';

    public const USED = 'used';

    /**
     * Validate a scanned QR code against the active event.
     *
     * @return array<string, mixed>
     */
    public function validate(string $code): array
    {
        $normalized = $this->normalize($code);

        $ticket = Ticket::query()
            ->forActiveEvent()
            ->with(['ticketCategory.event', 'checkedInBy'])
            ->where(function ($query) use ($normalized): void {
                $query->where('qr_code', $normalized)->orWhere('code', $normalized);
            })
            ->first();

        if (! $ticket) {
            return $this->payload(self::NOT_FOUND, $normalized);
        }

        return $this->payload(
            $ticket->checked_in_at === null ? self::VALID : self::USED,
            $normalized,
            $ticket,
        );
    }

    /**
     * Normalize a raw scanned value into the stored ticket code format.
     */
    public function normalize(string $code): string
    {
        return Str::upper(trim($code));
    }

    /**
     * Build the scanner response payload for a ticket lookup.
     *
     * @return array<string, mixed>
     */
    public function payload(string $status, string $code, ?Ticket $ticket = null, ?User $scanner = null): array
    {
        $checkedInAt = $ticket?->checked_in_at;
        $operatorName = $scanner?->name;

        return [
            'status' => $status,
            'message' => $this->messageFor($status),
            'code' => $code,
            'category' => $ticket?->ticketCategory?->name,
            'event' => $ticket?->event->name ?? $ticket?->ticketCategory?->event->name,
            'checked_in_at' => $checkedInAt?->toIso8601String(),
            'checked_in_date' => $checkedInAt?->translatedFormat('d M Y'),
            'checked_in_time' => $checkedInAt?->format('H:i'),
            'scanner' => $operatorName ?? $ticket?->checkedInBy?->name,
            'gate' => $ticket?->ticketCategory?->event?->location,
        ];
    }

    /**
     * Operator facing message for a scan status.
     */
    private function messageFor(string $status): string
    {
        return match ($status) {
            self::VALID => 'Tiket aktif dan belum digunakan.',
            self::USED => 'Tiket ini sudah digunakan sebelumnya.',
            self::NOT_FOUND => 'QR ini tidak terdaftar pada event aktif.',
            // Anything else is a completed check-in.
            default => 'Check-in berhasil. Siap untuk tiket berikutnya.',
        };
    }
}
