<?php

namespace App\Livewire\Admin;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\CheckInLog;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public string $dashboardDate = 'Minggu, 20 September 2026';

    public string $lastUpdated = '12 detik lalu';

    /**
     * @var array<int, string>
     */
    private const CATEGORY_COLORS = ['blue', 'emerald', 'amber'];

    private function activeEventRecord(): ?Event
    {
        return Event::query()
            ->active()
            ->orderByDesc('event_date')
            ->first();
    }

    /**
     * Event information for the active event card.
     *
     * @return array{name: string, date: string, location: string}
     */
    #[Computed]
    public function eventInformation(): array
    {
        $event = $this->activeEventRecord();

        if ($event === null) {
            return [
                'name' => 'Festival ABC 2026',
                'date' => '20 September 2026',
                'location' => 'Medan',
            ];
        }

        return [
            'name' => $event->name,
            'date' => $event->event_date->translatedFormat('d F Y'),
            'location' => $event->location ?? '-',
        ];
    }

    /**
     * Aggregate totals for tickets on the active event.
     *
     * @return array{total: int, checkedIn: int, remaining: int, checkedInPercentage: string, remainingPercentage: string}
     */
    #[Computed]
    public function statistics(): array
    {
        $total = Ticket::query()->forActiveEvent()->count();

        $checkedIn = Ticket::query()
            ->forActiveEvent()
            ->whereNotNull('checked_in_at')
            ->count();

        $remaining = max($total - $checkedIn, 0);

        return [
            'total' => $total,
            'checkedIn' => $checkedIn,
            'remaining' => $remaining,
            'checkedInPercentage' => $total === 0 ? '0.0' : number_format(($checkedIn / $total) * 100, 1),
            'remainingPercentage' => $total === 0 ? '0.0' : number_format(($remaining / $total) * 100, 1),
        ];
    }

    /**
     * Per-category ticket totals for the active event.
     *
     * @return array<int, array<string, string|int>>
     */
    #[Computed]
    public function ticketCategories(): array
    {
        $totals = $this->ticketCountsByCategory();
        $checkedInCounts = $this->ticketCountsByCategory(checkedInOnly: true);

        return TicketCategory::query()
            ->whereHas('event', fn (Builder $query) => $query->where('status', EventStatus::Active->value))
            ->orderBy('name')
            ->get()
            ->values()
            ->map(function (TicketCategory $category, int $index) use ($totals, $checkedInCounts): array {
                $total = $totals->get($category->id, 0);
                $checkedIn = $checkedInCounts->get($category->id, 0);

                return [
                    'name' => $category->name,
                    'label' => Str::upper($category->name),
                    'total' => $total,
                    'checkedIn' => $checkedIn,
                    'percentage' => $total === 0 ? '0.0' : number_format(($checkedIn / $total) * 100, 1),
                    'color' => self::CATEGORY_COLORS[$index % count(self::CATEGORY_COLORS)],
                    'testId' => Str::slug($category->name).'-category-progress',
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
     * The ten most recent check-in attempts.
     *
     * @return array<int, array<string, string|int>>
     */
    #[Computed]
    public function recentCheckIns(): array
    {
        return CheckInLog::query()
            ->with(['ticket.ticketCategory', 'scanner'])
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (CheckInLog $log): array => [
                'id' => $log->id,
                'qrCode' => $log->qr_code ?? '-',
                'category' => $log->ticket_id === null ? '-' : $log->ticket->ticketCategory->name,
                'scanner' => $log->scanner_id === null ? '-' : $log->scanner->name,
                'time' => $log->scanned_at->format('H:i:s'),
                'status' => $log->status,
            ])
            ->all();
    }

    /**
     * Users with the scanner role and their scan totals.
     *
     * @return array<int, array<string, string|int>>
     */
    #[Computed]
    public function scannerUsers(): array
    {
        return User::query()
            ->where('role', UserRole::Scanner->value)
            ->withCount('checkInLogs')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'scans' => (int) $user->check_in_logs_count,
            ])
            ->all();
    }
}
