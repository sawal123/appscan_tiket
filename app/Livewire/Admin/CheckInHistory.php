<?php

namespace App\Livewire\Admin;

use App\Models\CheckInLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Riwayat Check-in')]
class CheckInHistory extends Component
{
    public function render(): View
    {
        return view('livewire.admin.check-in-history', [
            'logs' => CheckInLog::query()
                ->with(['ticket.ticketCategory', 'scanner'])
                ->orderByDesc('scanned_at')
                ->latest()
                ->get(),
        ])->layoutData([
            'topbarTitle' => 'Riwayat Check-in',
            'topbarSubtitle' => 'Audit log scan tiket',
        ]);
    }
}
