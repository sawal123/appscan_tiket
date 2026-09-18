<?php

use App\Livewire\Admin\Dashboard;
use App\Models\CheckInLog;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function dashboardCategory(Event $event, string $name): TicketCategory
{
    return TicketCategory::factory()->for($event)->create(['name' => $name]);
}

function dashboardTicket(TicketCategory $category, bool $checkedIn = false, ?string $code = null): Ticket
{
    return Ticket::factory()
        ->for($category, 'ticketCategory')
        ->create([
            'event_id' => $category->event_id,
            'code' => $code ?? fake()->unique()->bothify('DASH-####'),
            'checked_in_at' => $checkedIn ? now() : null,
        ]);
}

function dashboardCheckInLog(Ticket $ticket, User $scanner, string $status = CheckInLog::STATUS_SUCCESS, ?CarbonInterface $scannedAt = null): CheckInLog
{
    return CheckInLog::create([
        'ticket_id' => $ticket->id,
        'scanner_id' => $scanner->id,
        'qr_code' => $ticket->qr_code,
        'status' => $status,
        'scanned_at' => $scannedAt ?? now(),
    ]);
}

test('admin dapat membuka dashboard', function () {
    $event = Event::factory()->active()->create(['name' => 'Festival Musik Medan']);
    $category = dashboardCategory($event, 'VIP');
    dashboardTicket($category, checkedIn: true, code: 'DASH-OPEN-1');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Total QR Terdaftar')
        ->assertSee('Festival Musik Medan');
});

test('statistik total ticket hanya menghitung ticket event aktif', function () {
    $active = Event::factory()->active()->create();
    $draft = Event::factory()->create();

    $activeCategory = dashboardCategory($active, 'Regular');
    $draftCategory = dashboardCategory($draft, 'Regular');

    foreach (range(1, 4) as $index) {
        dashboardTicket($activeCategory, code: "DASH-ACT-{$index}");
    }

    dashboardTicket($draftCategory, code: 'DASH-DRAFT-1');

    $statistics = Livewire::test(Dashboard::class)->instance()->statistics();

    expect($statistics['total'])->toBe(4);
});

test('checked in count benar', function () {
    $event = Event::factory()->active()->create();
    $category = dashboardCategory($event, 'VIP');

    dashboardTicket($category, checkedIn: true, code: 'DASH-CHK-1');
    dashboardTicket($category, checkedIn: true, code: 'DASH-CHK-2');
    dashboardTicket($category, checkedIn: true, code: 'DASH-CHK-3');
    dashboardTicket($category, code: 'DASH-CHK-4');

    $statistics = Livewire::test(Dashboard::class)->instance()->statistics();

    expect($statistics['checkedIn'])->toBe(3);
});

test('remaining dan progress check in benar', function () {
    $event = Event::factory()->active()->create();
    $category = dashboardCategory($event, 'Regular');

    dashboardTicket($category, checkedIn: true, code: 'DASH-REM-1');
    dashboardTicket($category, checkedIn: true, code: 'DASH-REM-2');
    dashboardTicket($category, code: 'DASH-REM-3');
    dashboardTicket($category, code: 'DASH-REM-4');
    dashboardTicket($category, code: 'DASH-REM-5');

    $statistics = Livewire::test(Dashboard::class)->instance()->statistics();

    expect($statistics['total'])->toBe(5)
        ->and($statistics['checkedIn'])->toBe(2)
        ->and($statistics['remaining'])->toBe(3)
        ->and($statistics['checkedInPercentage'])->toBe('40.0')
        ->and($statistics['remainingPercentage'])->toBe('60.0');
});

test('category statistic benar', function () {
    $event = Event::factory()->active()->create();
    $regular = dashboardCategory($event, 'Regular');
    $vip = dashboardCategory($event, 'VIP');

    dashboardTicket($regular, checkedIn: true, code: 'DASH-CAT-1');
    dashboardTicket($regular, checkedIn: true, code: 'DASH-CAT-2');
    dashboardTicket($regular, code: 'DASH-CAT-3');
    dashboardTicket($vip, checkedIn: true, code: 'DASH-CAT-4');

    $categories = Livewire::test(Dashboard::class)->instance()->ticketCategories();

    expect($categories)->toHaveCount(2)
        ->and($categories[0]['name'])->toBe('Regular')
        ->and($categories[0]['total'])->toBe(3)
        ->and($categories[0]['checkedIn'])->toBe(2)
        ->and($categories[0]['percentage'])->toBe('66.7')
        ->and($categories[1]['name'])->toBe('VIP')
        ->and($categories[1]['total'])->toBe(1)
        ->and($categories[1]['checkedIn'])->toBe(1)
        ->and($categories[1]['percentage'])->toBe('100.0');
});

test('recent check in menampilkan 10 log terbaru', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->active()->create();
    $category = dashboardCategory($event, 'Regular');
    $scanner = User::factory()->scanner()->create(['name' => 'Scanner Andi']);

    foreach (range(1, 12) as $index) {
        $ticket = dashboardTicket($category, checkedIn: true, code: sprintf('DASH-REC-%02d', $index));

        dashboardCheckInLog($ticket, $scanner, scannedAt: now()->subMinutes(12 - $index));
    }

    $component = Livewire::test(Dashboard::class);
    $recent = $component->instance()->recentCheckIns();

    expect($recent)->toHaveCount(10)
        ->and($recent[0]['qrCode'])->toBe('DASH-REC-12')
        ->and($recent[0]['category'])->toBe('Regular')
        ->and($recent[0]['scanner'])->toBe('Scanner Andi')
        ->and($recent[0]['status'])->toBe(CheckInLog::STATUS_SUCCESS);

    $component->assertSee('DASH-REC-12')
        ->assertSee('Scanner Andi')
        ->assertSee('Success')
        ->assertDontSee('DASH-REC-01');
});

test('scanner users hanya menampilkan user dengan role scanner', function () {
    $this->actingAs(User::factory()->admin()->create());

    $scanner = User::factory()->scanner()->create(['name' => 'Scanner Budi']);

    $event = Event::factory()->active()->create();
    $category = dashboardCategory($event, 'Regular');
    $ticket = dashboardTicket($category, checkedIn: true, code: 'DASH-SCAN-1');

    dashboardCheckInLog($ticket, $scanner);

    $scanners = Livewire::test(Dashboard::class)->instance()->scannerUsers();

    expect($scanners)->toHaveCount(1)
        ->and($scanners[0]['name'])->toBe('Scanner Budi')
        ->and($scanners[0]['scans'])->toBe(1);
});

test('scanner tidak dapat akses admin dashboard', function () {
    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});
