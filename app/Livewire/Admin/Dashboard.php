<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public string $activeEvent = 'Festival ABC 2026';

    public string $eventDate = '20 September 2026';

    public string $eventLocation = 'Medan';

    public string $dashboardDate = 'Minggu, 20 September 2026';

    public string $lastUpdated = '12 detik lalu';

    public int $totalTickets = 7000;

    public int $verifiedTickets = 4328;

    public int $unverifiedTickets = 2672;

    public int $activeScanners = 8;

    public int $maxScanners = 10;

    /**
     * @var array<int, array<string, string|int>>
     */
    public array $ticketCategories = [
        [
            'name' => 'Regular',
            'label' => 'REGULAR',
            'verified' => 3100,
            'total' => 4000,
            'percentage' => '77.5',
            'color' => 'blue',
            'testId' => 'regular-category-progress',
        ],
        [
            'name' => 'VIP',
            'label' => 'VIP',
            'verified' => 900,
            'total' => 2000,
            'percentage' => '45',
            'color' => 'emerald',
            'testId' => 'vip-category-progress',
        ],
        [
            'name' => 'VVIP',
            'label' => 'VVIP',
            'verified' => 328,
            'total' => 1000,
            'percentage' => '32.8',
            'color' => 'amber',
            'testId' => 'vvip-category-progress',
        ],
    ];

    /**
     * @var array<int, array<string, string>>
     */
    public array $recentVerifications = [
        [
            'qrCode' => 'QR-006823',
            'category' => 'VIP',
            'time' => '19:42:18',
            'scanner' => 'Gate 02',
            'status' => 'Verified',
        ],
        [
            'qrCode' => 'QR-006822',
            'category' => 'Regular',
            'time' => '19:42:11',
            'scanner' => 'Gate 01',
            'status' => 'Verified',
        ],
        [
            'qrCode' => 'QR-006821',
            'category' => 'VVIP',
            'time' => '19:41:55',
            'scanner' => 'Gate 04',
            'status' => 'Verified',
        ],
        [
            'qrCode' => 'QR-006820',
            'category' => 'Regular',
            'time' => '19:41:39',
            'scanner' => 'Gate 03',
            'status' => 'Verified',
        ],
        [
            'qrCode' => 'QR-006819',
            'category' => 'VIP',
            'time' => '19:41:21',
            'scanner' => 'Gate 02',
            'status' => 'Verified',
        ],
    ];

    /**
     * @var array<int, array<string, string|int>>
     */
    public array $scannerStatuses = [
        [
            'gate' => 'Gate 01',
            'status' => 'Online',
            'scans' => 128,
            'testId' => 'scanner-gate-01-status',
        ],
        [
            'gate' => 'Gate 02',
            'status' => 'Online',
            'scans' => 134,
            'testId' => 'scanner-gate-02-status',
        ],
        [
            'gate' => 'Gate 03',
            'status' => 'Online',
            'scans' => 119,
            'testId' => 'scanner-gate-03-status',
        ],
        [
            'gate' => 'Gate 04',
            'status' => 'Offline',
            'scans' => 87,
            'testId' => 'scanner-gate-04-status',
        ],
    ];

    #[Computed]
    public function checkInPercentage(): string
    {
        return number_format(($this->verifiedTickets / $this->totalTickets) * 100, 1);
    }
}
