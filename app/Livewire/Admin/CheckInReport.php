<?php

namespace App\Livewire\Admin;

use App\Enums\EventStatus;
use App\Models\CheckInLog;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Laporan Check-in')]
class CheckInReport extends Component
{
    public const PERIOD_ALL = 'all';

    public const PERIOD_TODAY = 'today';

    public string $period = self::PERIOD_ALL;

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function periodOptions(): array
    {
        return [
            ['value' => self::PERIOD_ALL, 'label' => 'Semua Waktu'],
            ['value' => self::PERIOD_TODAY, 'label' => 'Hari Ini'],
        ];
    }

    /**
     * Scans tied to a ticket of the active event. Invalid scans carry no ticket,
     * so they can never be attributed to an event.
     *
     * @return Builder<CheckInLog>
     */
    private function eventScanQuery(): Builder
    {
        return CheckInLog::query()
            ->whereHas('ticket', fn (Builder $query) => $query->forActiveEvent())
            ->when(
                $this->period === self::PERIOD_TODAY,
                fn (Builder $query) => $query->where('scanned_at', '>=', now()->startOfDay()),
            );
    }

    /**
     * Invalid scans are counted for the period only, because they have no ticket
     * and therefore no event to attribute them to.
     *
     * @return Builder<CheckInLog>
     */
    private function invalidScanQuery(): Builder
    {
        return CheckInLog::query()
            ->where('status', CheckInLog::STATUS_INVALID)
            ->when(
                $this->period === self::PERIOD_TODAY,
                fn (Builder $query) => $query->where('scanned_at', '>=', now()->startOfDay()),
            );
    }

    /**
     * Ticket totals for the active event plus the scan counts per log status.
     *
     * @return array{total: int, success: int, alreadyCheckedIn: int, invalid: int}
     */
    #[Computed]
    public function summary(): array
    {
        $counts = $this->eventScanQuery()
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->get()
            ->pluck('aggregate', 'status')
            ->all();

        $countFor = fn (string $status): int => (int) ($counts[$status] ?? 0);

        return [
            'total' => Ticket::query()->forActiveEvent()->count(),
            'success' => $countFor(CheckInLog::STATUS_SUCCESS),
            'alreadyCheckedIn' => $countFor(CheckInLog::STATUS_ALREADY_CHECKED_IN),
            'invalid' => $this->invalidScanQuery()->count(),
        ];
    }

    /**
     * Per-category ticket totals for the active event.
     *
     * @return array<int, array<string, string|int>>
     */
    #[Computed]
    public function categoryBreakdown(): array
    {
        $totals = $this->ticketCountsByCategory();
        $checkedIn = $this->ticketCountsByCategory(checkedInOnly: true);

        return TicketCategory::query()
            ->whereHas('event', fn (Builder $query) => $query->where('status', EventStatus::Active->value))
            ->orderBy('name')
            ->get()
            ->values()
            ->map(function (TicketCategory $category) use ($totals, $checkedIn): array {
                $total = $totals->get($category->id, 0);
                $scanned = $checkedIn->get($category->id, 0);

                return [
                    'name' => $category->name,
                    'total' => $total,
                    'checkedIn' => $scanned,
                    'percentage' => $total === 0 ? '0.0' : number_format(($scanned / $total) * 100, 1),
                    'testId' => Str::slug($category->name).'-report-row',
                ];
            })
            ->all();
    }

    /**
     * Count tickets per category on the active event without loading the tickets.
     *
     * @return Collection<int, int>
     */
    private function ticketCountsByCategory(bool $checkedInOnly = false): Collection
    {
        return Ticket::query()
            ->forActiveEvent()
            ->when($checkedInOnly, fn (Builder $query) => $query->whereNotNull('checked_in_at'))
            ->groupBy('ticket_category_id')
            ->selectRaw('ticket_category_id, COUNT(*) as aggregate')
            ->get()
            ->pluck('aggregate', 'ticket_category_id')
            ->map(fn (mixed $count): int => (int) $count);
    }

    /**
     * Scan volume per scanner for the selected period, ranked highest first.
     *
     * @return array<int, array{id: int, name: string, scans: int}>
     */
    #[Computed]
    public function scannerBreakdown(): array
    {
        $totals = $this->eventScanQuery()
            ->whereNotNull('scanner_id')
            ->groupBy('scanner_id')
            ->selectRaw('scanner_id, COUNT(*) as aggregate')
            ->get()
            ->pluck('aggregate', 'scanner_id')
            ->all();

        if ($totals === []) {
            return [];
        }

        $names = User::query()->whereKey(array_keys($totals))->pluck('name', 'id');

        $scanners = [];

        foreach ($totals as $scannerId => $count) {
            $scanners[] = [
                'id' => (int) $scannerId,
                'name' => (string) $names->get((int) $scannerId, 'Scanner'),
                'scans' => (int) $count,
            ];
        }

        usort($scanners, fn (array $first, array $second): int => $second['scans'] <=> $first['scans']);

        return $scanners;
    }

    public function render(): View
    {
        return view('livewire.admin.check-in-report')->layoutData([
            'topbarTitle' => 'Laporan Check-in',
            'topbarSubtitle' => 'Rekap aktivitas scan tiket',
        ]);
    }
}
