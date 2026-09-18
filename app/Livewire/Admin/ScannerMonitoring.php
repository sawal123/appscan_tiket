<?php

namespace App\Livewire\Admin;

use App\Models\CheckInLog;
use App\Models\ScannerSession;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Monitoring Scanner')]
class ScannerMonitoring extends Component
{
    public function render(): View
    {
        $scanTotals = CheckInLog::query()
            ->where('status', CheckInLog::STATUS_SUCCESS)
            ->whereNotNull('scanner_id')
            ->whereHas('ticket', fn (Builder $query) => $query->forActiveEvent())
            ->groupBy('scanner_id')
            ->selectRaw('scanner_id, COUNT(*) as aggregate')
            ->get()
            ->pluck('aggregate', 'scanner_id')
            ->map(fn (mixed $count): int => (int) $count);

        return view('livewire.admin.scanner-monitoring', [
            'sessions' => ScannerSession::query()
                ->with('user:id,name,email')
                ->orderByDesc('last_seen_at')
                ->get(),
            'scanTotals' => $scanTotals,
        ])->layoutData([
            'topbarTitle' => 'Monitoring Scanner',
            'topbarSubtitle' => 'Status perangkat scanner saat event',
        ]);
    }
}
