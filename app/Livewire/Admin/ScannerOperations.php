<?php

namespace App\Livewire\Admin;

use App\Models\CheckInLog;
use App\Models\ScannerSession;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Operasional Scanner')]
class ScannerOperations extends Component
{
    private const IDLE_THRESHOLD_MINUTES = 10;

    /**
     * @return Collection<int, int>
     */
    private function scanCountsByScanner(): Collection
    {
        return $this->activeEventLogs()
            ->whereNotNull('scanner_id')
            ->groupBy('scanner_id')
            ->selectRaw('scanner_id, COUNT(*) as aggregate')
            ->get()
            ->pluck('aggregate', 'scanner_id')
            ->map(fn (mixed $count): int => (int) $count);
    }

    /**
     * @return Collection<int, string|null>
     */
    private function lastScansByScanner(): Collection
    {
        return $this->activeEventLogs()
            ->whereNotNull('scanner_id')
            ->groupBy('scanner_id')
            ->selectRaw('scanner_id, MAX(scanned_at) as last_scan_at')
            ->get()
            ->pluck('last_scan_at', 'scanner_id');
    }

    /**
     * @return array{online: int, totalToday: int, recent: int, idle: int}
     */
    #[Computed]
    public function summary(): array
    {
        $onlineSessions = ScannerSession::query()
            ->where('last_seen_at', '>=', now()->subMinutes(ScannerSession::ONLINE_THRESHOLD_MINUTES))
            ->with('user:id')
            ->get();

        $lastScans = $this->lastScansByScanner();

        $idle = $onlineSessions
            ->filter(function (ScannerSession $session) use ($lastScans): bool {
                $lastScan = $lastScans->get($session->user_id);

                return $lastScan === null || Carbon::parse($lastScan)->lessThan(now()->subMinutes(self::IDLE_THRESHOLD_MINUTES));
            })
            ->count();

        return [
            'online' => $onlineSessions->count(),
            'totalToday' => $this->activeEventLogs()
                ->whereDate('scanned_at', today())
                ->count(),
            'recent' => $this->activeEventLogs()
                ->where('scanned_at', '>=', now()->subMinutes(self::IDLE_THRESHOLD_MINUTES))
                ->count(),
            'idle' => $idle,
        ];
    }

    /**
     * @return array<int, array{id: int, scanner: string, event: string, device: string, status: string, statusVariant: string, lastSeen: string, lastScan: string, scans: int}>
     */
    #[Computed]
    public function scannerRows(): array
    {
        $scanCounts = $this->scanCountsByScanner();
        $lastScans = $this->lastScansByScanner();

        return ScannerSession::query()
            ->with('user.scannerEventAssignment.event:id,name')
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(function (ScannerSession $session) use ($lastScans, $scanCounts): array {
                $lastScanValue = $lastScans->get($session->user_id);
                $lastScan = $lastScanValue ? Carbon::parse($lastScanValue) : null;
                $online = $session->last_seen_at->greaterThanOrEqualTo(now()->subMinutes(ScannerSession::ONLINE_THRESHOLD_MINUTES));
                $idle = $online && ($lastScan === null || $lastScan->lessThan(now()->subMinutes(self::IDLE_THRESHOLD_MINUTES)));
                $status = $online ? ($idle ? 'Idle' : 'Online') : 'Offline';
                $assignment = $session->user->scannerEventAssignment;

                return [
                    'id' => $session->id,
                    'scanner' => $session->user->name,
                    'event' => $assignment ? $assignment->event->name : '-',
                    'device' => $session->device_name ?? '-',
                    'status' => $status,
                    'statusVariant' => match ($status) {
                        'Online' => 'emerald',
                        'Idle' => 'amber',
                        default => 'slate',
                    },
                    'lastSeen' => $session->last_seen_at->translatedFormat('d M Y H:i'),
                    'lastScan' => $lastScan?->translatedFormat('d M Y H:i') ?? '-',
                    'scans' => $scanCounts->get($session->user_id, 0),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{id: int, time: string, scanner: string, qr: string, category: string}>
     */
    #[Computed]
    public function lastActivities(): array
    {
        return $this->activeEventLogs()
            ->whereNotNull('scanner_id')
            ->with(['scanner:id,name', 'ticket.ticketCategory:id,name'])
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (CheckInLog $log): array => [
                'id' => $log->id,
                'time' => $log->scanned_at->translatedFormat('d M Y H:i:s'),
                'scanner' => $log->scanner->name,
                'qr' => $log->qr_code ?? '-',
                'category' => $log->ticket->ticketCategory->name,
            ])
            ->all();
    }

    public function render(): View
    {
        return view('livewire.admin.scanner-operations')->layoutData([
            'topbarTitle' => 'Operasional Scanner',
            'topbarSubtitle' => 'Monitoring aktivitas scanner event aktif',
        ]);
    }

    /**
     * @return Builder<CheckInLog>
     */
    private function activeEventLogs(): Builder
    {
        return CheckInLog::query()
            ->whereHas('ticket', fn (Builder $query) => $query->forActiveEvent());
    }
}
