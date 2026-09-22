<?php

namespace App\Http\Controllers\Scanner;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\ScannerSession;
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
        $assignedEvent = $this->assignedEvent();
        $activeEvent = auth()->user()?->isScanner() ? $this->activeAssignedEvent($assignedEvent) : $this->activeEvent();

        return view('scanner.index', [
            'activeEvent' => $activeEvent,
            'assignedEvent' => $assignedEvent,
            ...$this->scannerContext(),
        ]);
    }

    public function verified(Request $request): View
    {
        $assignedEvent = $this->assignedEvent();
        $activeEvent = $request->user()?->isScanner() ? $this->activeAssignedEvent($assignedEvent) : $this->activeEvent();
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->integer('category') ?: null;

        $tickets = Ticket::query()
            ->select(['id', 'event_id', 'ticket_category_id', 'qr_code', 'checked_in_at', 'checked_in_by'])
            ->when($activeEvent, fn ($query) => $query->where('event_id', $activeEvent->id), fn ($query) => $query->whereRaw('1 = 0'))
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
            'assignedEvent' => $assignedEvent,
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

        return response()->json($this->validation->validate($validated['code'], $request->user()));
    }

    public function checkIn(Request $request, TicketCheckInService $checkIn): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = $checkIn->checkIn($validated['code'], $request->user());

        if ($payload['status'] === TicketCheckInService::SUCCESS) {
            $this->touchScannerSession($request, $validated, true);
        }

        return response()->json($payload);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $session = $this->touchScannerSession($request, $validated);

        return response()->json([
            'status' => 'ok',
            'session_id' => $session->id,
            'last_seen_at' => $session->last_seen_at->toIso8601String(),
        ]);
    }

    private function activeEvent(): ?Event
    {
        return Event::query()
            ->active()
            ->orderByDesc('event_date')
            ->first();
    }

    private function assignedEvent(): ?Event
    {
        $user = auth()->user();

        if (! $user?->isScanner()) {
            return null;
        }

        return $user->scannerEventAssignment()
            ->with('event')
            ->first()
            ?->event;
    }

    private function activeAssignedEvent(?Event $event): ?Event
    {
        return $event?->status === EventStatus::Active ? $event : null;
    }

    /**
     * @return array{scannerName: string, scannerInitials: string, scannerRole: string}
     */
    private function scannerContext(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [
                'scannerName' => 'Scanner',
                'scannerInitials' => 'S',
                'scannerRole' => 'Scanner',
            ];
        }

        return [
            'scannerName' => $user->name,
            'scannerInitials' => $user->initials(),
            'scannerRole' => ucfirst($user->role->value),
        ];
    }

    /**
     * @param  array{device_id?: string|null, device_name?: string|null}  $data
     */
    private function touchScannerSession(Request $request, array $data, bool $markScan = false): ScannerSession
    {
        $user = $request->user();
        $deviceId = filled($data['device_id'] ?? null) ? (string) $data['device_id'] : null;
        $userAgent = substr((string) $request->userAgent(), 0, 1000);
        $ipAddress = $request->ip();

        $query = ScannerSession::query()->where('user_id', $user->id);

        if ($deviceId !== null) {
            $query->where('device_id', $deviceId);
        } else {
            $query
                ->whereNull('device_id')
                ->where('ip_address', $ipAddress)
                ->where('user_agent', $userAgent);
        }

        $session = $query->firstOrNew([
            'user_id' => $user->id,
            'device_id' => $deviceId,
        ]);

        $session->forceFill([
            'device_name' => filled($data['device_name'] ?? null) ? (string) $data['device_name'] : $session->device_name,
            'last_seen_at' => now(),
            'last_scan_at' => $markScan ? now() : $session->last_scan_at,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ])->save();

        return $session;
    }
}
