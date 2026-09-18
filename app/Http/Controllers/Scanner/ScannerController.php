<?php

namespace App\Http\Controllers\Scanner;

use App\Http\Controllers\Controller;
use App\Models\CheckInLog;
use App\Models\Event;
use App\Models\Ticket;
use App\Services\TicketCheckInService;
use App\Services\TicketValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScannerController extends Controller
{
    public function __construct(
        private readonly TicketValidationService $validation,
    ) {}

    public function index(): View
    {
        return view('scanner.index', [
            'activeEvent' => $this->activeEvent(),
            ...$this->scannerContext(),
        ]);
    }

    public function verified(): View
    {
        $loggedTickets = CheckInLog::query()
            ->where('status', CheckInLog::STATUS_SUCCESS)
            ->whereHas('ticket', fn ($query) => $query->forActiveEvent())
            ->with(['ticket.ticketCategory.event'])
            ->orderByDesc('scanned_at')
            ->get()
            ->map(fn (CheckInLog $log): array => [
                'code' => $log->ticket->qr_code,
                'category' => $log->ticket?->ticketCategory?->name,
                'time' => $log->scanned_at->format('H:i'),
                'scanned_at' => $log->scanned_at,
                'gate' => $log->ticket?->ticketCategory?->event?->location,
            ]);

        $legacyTickets = Ticket::query()
            ->forActiveEvent()
            ->whereNotNull('checked_in_at')
            ->whereDoesntHave('checkInLogs', fn ($query) => $query->where('status', CheckInLog::STATUS_SUCCESS))
            ->with('ticketCategory.event')
            ->orderByDesc('checked_in_at')
            ->get()
            ->map(fn (Ticket $ticket): array => [
                'code' => $ticket->qr_code,
                'category' => $ticket->ticketCategory?->name,
                'time' => $ticket->checked_in_at?->format('H:i'),
                'scanned_at' => $ticket->checked_in_at,
                'gate' => $ticket->ticketCategory?->event?->location,
            ]);

        $tickets = collect($loggedTickets->all())
            ->merge($legacyTickets)
            ->sortByDesc('scanned_at')
            ->map(fn (array $ticket): array => [
                'code' => $ticket['code'],
                'category' => $ticket['category'],
                'time' => $ticket['time'],
                'gate' => $ticket['gate'],
            ])
            ->values()
            ->all();

        return view('scanner.verified', [
            'activeEvent' => $this->activeEvent(),
            'tickets' => $tickets,
            ...$this->scannerContext(),
        ]);
    }

    public function validateTicket(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        return response()->json($this->validation->validate($validated['code']));
    }

    public function checkIn(Request $request, TicketCheckInService $checkIn): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        return response()->json($checkIn->checkIn($validated['code'], $request->user()));
    }

    private function activeEvent(): ?Event
    {
        return Event::query()
            ->active()
            ->orderByDesc('event_date')
            ->first();
    }

    /**
     * @return array{scannerName: string, scannerInitials: string}
     */
    private function scannerContext(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [
                'scannerName' => 'Scanner',
                'scannerInitials' => 'S',
            ];
        }

        return [
            'scannerName' => $user->name,
            'scannerInitials' => $user->initials(),
        ];
    }
}
