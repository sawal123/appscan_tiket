<?php

use App\Livewire\Admin\CheckInReport;
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

function monitorEvent(string $name = 'Event Monitoring'): Event
{
    return Event::factory()->active()->create(['name' => $name]);
}

function monitorCategory(Event $event, string $name): TicketCategory
{
    return TicketCategory::factory()->for($event)->create(['name' => $name]);
}

function monitorTicket(TicketCategory $category, bool $checkedIn = false, ?string $code = null): Ticket
{
    return Ticket::factory()
        ->for($category, 'ticketCategory')
        ->create([
            'event_id' => $category->event_id,
            'code' => $code ?? fake()->unique()->bothify('MON-####'),
            'checked_in_at' => $checkedIn ? now() : null,
        ]);
}

function monitorLog(
    ?Ticket $ticket,
    User $scanner,
    string $status = CheckInLog::STATUS_SUCCESS,
    ?CarbonInterface $scannedAt = null,
    ?string $qrCode = null,
): CheckInLog {
    return CheckInLog::create([
        'ticket_id' => $ticket?->id,
        'scanner_id' => $scanner->id,
        'qr_code' => $qrCode ?? $ticket?->qr_code,
        'status' => $status,
        'scanned_at' => $scannedAt ?? now(),
    ]);
}

test('admin dapat melihat monitoring', function () {
    $event = monitorEvent('Festival Pantai');
    monitorCategory($event, 'VIP');

    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Aktivitas Scanner')
        ->assertSee('20 aktivitas check-in terakhir')
        ->assertSee('Festival Pantai');

    $this->get(route('admin.reports.check-in'))
        ->assertOk()
        ->assertSee('Laporan Check-in')
        ->assertSee('Rincian Kategori')
        ->assertSee('Aktivitas Scanner')
        ->assertSee('Hari Ini');
});

test('scanner tidak bisa akses report', function () {
    $this->get(route('admin.reports.check-in'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('admin.reports.check-in'))
        ->assertForbidden();
});

test('statistik ticket benar', function () {
    $event = monitorEvent();
    $category = monitorCategory($event, 'Regular');
    $scanner = User::factory()->scanner()->create();

    $first = monitorTicket($category, checkedIn: true, code: 'MON-T1');
    $second = monitorTicket($category, checkedIn: true, code: 'MON-T2');
    monitorTicket($category, code: 'MON-T3');

    monitorLog($first, $scanner, CheckInLog::STATUS_SUCCESS);
    monitorLog($second, $scanner, CheckInLog::STATUS_SUCCESS);
    monitorLog($second, $scanner, CheckInLog::STATUS_ALREADY_CHECKED_IN);
    monitorLog(null, $scanner, CheckInLog::STATUS_INVALID, qrCode: 'TIDAK-DIKENAL');

    $summary = Livewire::test(CheckInReport::class)->instance()->summary();

    expect($summary['total'])->toBe(3)
        ->and($summary['success'])->toBe(2)
        ->and($summary['alreadyCheckedIn'])->toBe(1)
        ->and($summary['invalid'])->toBe(1);
});

test('scanner activity benar', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = monitorEvent();
    $category = monitorCategory($event, 'Regular');

    $first = monitorTicket($category, checkedIn: true, code: 'MON-A1');
    $second = monitorTicket($category, checkedIn: true, code: 'MON-A2');

    $andi = User::factory()->scanner()->create(['name' => 'Andi']);
    $budi = User::factory()->scanner()->create(['name' => 'Budi']);

    monitorLog($first, $andi, CheckInLog::STATUS_SUCCESS);
    monitorLog($second, $andi, CheckInLog::STATUS_SUCCESS);
    monitorLog($second, $budi, CheckInLog::STATUS_ALREADY_CHECKED_IN);

    $activity = Livewire::test(Dashboard::class)->instance()->scannerActivity();

    expect($activity)->toHaveCount(2)
        ->and($activity[0]['name'])->toBe('Andi')
        ->and($activity[0]['scans'])->toBe(2)
        ->and($activity[1]['name'])->toBe('Budi')
        ->and($activity[1]['scans'])->toBe(1);

    $scanners = Livewire::test(CheckInReport::class)->instance()->scannerBreakdown();

    expect($scanners)->toHaveCount(2)
        ->and($scanners[0]['name'])->toBe('Andi')
        ->and($scanners[0]['scans'])->toBe(2);
});

test('recent check in menampilkan 20 log terbaru', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = monitorEvent();
    $category = monitorCategory($event, 'Regular');
    $scanner = User::factory()->scanner()->create(['name' => 'Scanner Andi']);

    foreach (range(1, 22) as $index) {
        $ticket = monitorTicket($category, checkedIn: true, code: sprintf('MON-REC-%02d', $index));

        monitorLog($ticket, $scanner, scannedAt: now()->subMinutes(22 - $index));
    }

    $component = Livewire::test(Dashboard::class);
    $recent = $component->instance()->recentCheckIns();

    expect($recent)->toHaveCount(20)
        ->and($recent[0]['qrCode'])->toBe('MON-REC-22')
        ->and($recent[0]['category'])->toBe('Regular')
        ->and($recent[0]['scanner'])->toBe('Scanner Andi')
        ->and($recent[0]['status'])->toBe(CheckInLog::STATUS_SUCCESS);

    $component->assertSee('MON-REC-22')
        ->assertSee('Scanner Andi')
        ->assertSee('Success')
        ->assertDontSee('MON-REC-01');
});

test('kategori statistik benar', function () {
    $event = monitorEvent();
    $regular = monitorCategory($event, 'Regular');
    $vip = monitorCategory($event, 'VIP');

    monitorTicket($regular, checkedIn: true, code: 'MON-C1');
    monitorTicket($regular, checkedIn: true, code: 'MON-C2');
    monitorTicket($regular, code: 'MON-C3');
    monitorTicket($vip, checkedIn: true, code: 'MON-C4');

    $breakdown = Livewire::test(CheckInReport::class)->instance()->categoryBreakdown();

    expect($breakdown)->toHaveCount(2)
        ->and($breakdown[0]['name'])->toBe('Regular')
        ->and($breakdown[0]['total'])->toBe(3)
        ->and($breakdown[0]['checkedIn'])->toBe(2)
        ->and($breakdown[0]['percentage'])->toBe('66.7')
        ->and($breakdown[1]['name'])->toBe('VIP')
        ->and($breakdown[1]['total'])->toBe(1)
        ->and($breakdown[1]['checkedIn'])->toBe(1)
        ->and($breakdown[1]['percentage'])->toBe('100.0');
});

test('filter hari ini bekerja', function () {
    $event = monitorEvent();
    $category = monitorCategory($event, 'Regular');
    $scanner = User::factory()->scanner()->create(['name' => 'Andi']);

    $todayTicket = monitorTicket($category, checkedIn: true, code: 'MON-D1');
    $olderTicket = monitorTicket($category, checkedIn: true, code: 'MON-D2');

    monitorLog($todayTicket, $scanner, scannedAt: now()->startOfDay()->addHour());
    monitorLog($olderTicket, $scanner, scannedAt: now()->subDay());
    monitorLog(null, $scanner, CheckInLog::STATUS_INVALID, scannedAt: now()->subDay(), qrCode: 'LAMA-001');

    $component = Livewire::test(CheckInReport::class);

    expect($component->instance()->summary()['success'])->toBe(2)
        ->and($component->instance()->summary()['invalid'])->toBe(1);

    $component->set('period', 'today');

    $todaySummary = $component->instance()->summary();
    $todayScanners = $component->instance()->scannerBreakdown();

    expect($todaySummary['success'])->toBe(1)
        ->and($todaySummary['invalid'])->toBe(0)
        ->and($todayScanners)->toHaveCount(1)
        ->and($todayScanners[0]['scans'])->toBe(1);
});
