<?php

namespace App\Http\Controllers\Scanner;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketCategory;
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

    public function verified(Request $request): View
    {
        $activeEvent = $this->activeEvent();
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->integer('category') ?: null;

        $tickets = Ticket::query()
            ->select(['id', 'event_id', 'ticket_category_id', 'qr_code', 'checked_in_at', 'checked_in_by'])
            ->forActiveEvent()
            ->whereNotNull('checked_in_at')
            ->with(['ticketCategory:id,name,event_id', 'checkedInBy:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('qr_code', 'like', '%'.Ticket::normalizeQrCode($search).'%');
            })
            ->when($categoryId, fn ($query) => $query->where('ticket_category_id', $categoryId))
            ->orderByDesc('checked_in_at')
            ->paginate(15)
            ->withQueryString();

        return view('scanner.verified', [
            'activeEvent' => $activeEvent,
            'tickets' => $tickets,
            'categories' => $activeEvent
                ? TicketCategory::query()
                    ->select(['id', 'name'])
                    ->where('event_id', $activeEvent->id)
                    ->orderBy('name')
                    ->get()
                : collect(),
            'search' => $search,
            'categoryFilter' => $categoryId,
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
