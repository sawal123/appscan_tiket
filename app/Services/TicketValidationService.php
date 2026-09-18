<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Str;

class TicketValidationService
{
    public const INVALID = 'invalid';

    public const NOT_FOUND = 'not_found';

    public const VALID = 'valid';

    public const USED = 'used';

    /**
     * Validate a scanned QR code against the scanner assigned event.
     *
     * @return array<string, mixed>
     */
    public function validate(string $code, ?User $user = null): array
    {
        $normalized = $this->normalize($code);
        $eventId = $this->eventIdFor($user);

        if ($user?->isScanner() && $eventId === null) {
            return $this->payload(self::INVALID, $normalized, message: 'Scanner belum memiliki event. Hubungi administrator.');
        }

        $ticket = Ticket::query()
            ->when($eventId !== null, fn ($query) => $query->where('event_id', $eventId), fn ($query) => $query->forActiveEvent())
            ->with(['ticketCategory.event', 'checkedInBy'])
            ->where(function ($query) use ($normalized): void {
                $query->where('qr_code', $normalized)->orWhere('code', $normalized);
            })
            ->first();

        if (! $ticket) {
            if ($this->ticketExistsOutsideEvent($normalized, $eventId)) {
                return $this->payload(self::INVALID, $normalized, message: 'Tiket bukan untuk event scanner ini.');
            }

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
    public function payload(string $status, string $code, ?Ticket $ticket = null, ?User $scanner = null, ?string $message = null): array
    {
        $checkedInAt = $ticket?->checked_in_at;
        $operatorName = $scanner?->name;

        return [
            'status' => $status,
            'message' => $message ?? $this->messageFor($status),
            'code' => $code,
            'category' => $ticket?->ticketCategory?->name,
            // The category event is eager loaded by both callers, so reading it first
            // avoids an extra lazy load of the ticket's own event on every scan.
            'event' => $ticket?->ticketCategory->event->name ?? $ticket?->event?->name,
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
            self::INVALID => 'Tiket bukan untuk event scanner ini.',
            self::NOT_FOUND => 'QR ini tidak terdaftar pada event aktif.',
            // Anything else is a completed check-in.
            default => 'Check-in berhasil. Siap untuk tiket berikutnya.',
        };
    }

    private function eventIdFor(?User $user): ?int
    {
        if (! $user?->isScanner()) {
            return null;
        }

        $assignment = $user->scannerEventAssignment;

        if (! $assignment) {
            $assignment = $user->scannerEventAssignment()->first();
        }

        return $assignment?->event_id;
    }

    private function ticketExistsOutsideEvent(string $code, ?int $eventId): bool
    {
        if ($eventId === null) {
            return false;
        }

        return Ticket::query()
            ->where('event_id', '<>', $eventId)
            ->where(function ($query) use ($code): void {
                $query->where('qr_code', $code)->orWhere('code', $code);
            })
            ->exists();
    }
}
