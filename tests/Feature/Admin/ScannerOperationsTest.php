<?php

use App\Enums\EventStatus;
use App\Livewire\Admin\ScannerOperations;
use App\Models\CheckInLog;
use App\Models\Event;
use App\Models\ScannerSession;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function operationsEvent(string $name = 'Event Aktif'): Event
{
    return Event::factory()->active()->create(['name' => $name]);
}

function operationsCategory(Event $event, string $name = 'VIP'): TicketCategory
{
    return TicketCategory::factory()->for($event)->create(['name' => $name]);
}

function operationsTicket(TicketCategory $category, string $code): Ticket
{
    return Ticket::factory()->for($category, 'ticketCategory')->create([
        'event_id' => $category->event_id,
        'code' => $code,
        'qr_code' => $code,
        'checked_in_at' => now(),
    ]);
}

function operationsLog(Ticket $ticket, User $scanner, int $minutesAgo = 0): CheckInLog
{
    return CheckInLog::create([
        'ticket_id' => $ticket->id,
        'scanner_id' => $scanner->id,
        'qr_code' => $ticket->qr_code,
        'status' => CheckInLog::STATUS_SUCCESS,
        'scanned_at' => now()->subMinutes($minutesAgo),
    ]);
}

function operationsSession(User $scanner, int $seenMinutesAgo, string $device = 'Scanner Device'): ScannerSession
{
    return ScannerSession::create([
        'user_id' => $scanner->id,
        'device_id' => 'device-'.$scanner->id,
        'device_name' => $device,
        'last_seen_at' => now()->subMinutes($seenMinutesAgo),
    ]);
}

test('admin access', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.scanners.operations'))
        ->assertOk()
        ->assertSee('Operasional Scanner');
});

test('scanner forbidden', function () {
    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('admin.scanners.operations'))
        ->assertForbidden();
});

test('guest redirect', function () {
    $this->get(route('admin.scanners.operations'))
        ->assertRedirect(route('login'));
});

test('online status', function () {
    $scanner = User::factory()->scanner()->create(['name' => 'Online Scanner']);
    operationsSession($scanner, seenMinutesAgo: 1, device: 'Online Device');

    $event = operationsEvent();
    $category = operationsCategory($event);
    operationsLog(operationsTicket($category, 'OPS-ONLINE'), $scanner, minutesAgo: 1);

    $rows = Livewire::test(ScannerOperations::class)->instance()->scannerRows();

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['scanner'])->toBe('Online Scanner')
        ->and($rows[0]['status'])->toBe('Online')
        ->and($rows[0]['scans'])->toBe(1);
});

test('offline status', function () {
    $scanner = User::factory()->scanner()->create(['name' => 'Offline Scanner']);
    operationsSession($scanner, seenMinutesAgo: 3, device: 'Offline Device');

    $rows = Livewire::test(ScannerOperations::class)->instance()->scannerRows();

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['status'])->toBe('Offline');
});

test('idle status', function () {
    $scanner = User::factory()->scanner()->create(['name' => 'Idle Scanner']);
    operationsSession($scanner, seenMinutesAgo: 1, device: 'Idle Device');

    $event = operationsEvent();
    $category = operationsCategory($event);
    operationsLog(operationsTicket($category, 'OPS-IDLE'), $scanner, minutesAgo: 11);

    $rows = Livewire::test(ScannerOperations::class)->instance()->scannerRows();
    $summary = Livewire::test(ScannerOperations::class)->instance()->summary();

    expect($rows[0]['status'])->toBe('Idle')
        ->and($summary['idle'])->toBe(1);
});

test('scan velocity', function () {
    $scanner = User::factory()->scanner()->create();
    operationsSession($scanner, seenMinutesAgo: 1);

    $event = operationsEvent();
    $category = operationsCategory($event);

    operationsLog(operationsTicket($category, 'OPS-VEL-1'), $scanner, minutesAgo: 1);
    operationsLog(operationsTicket($category, 'OPS-VEL-2'), $scanner, minutesAgo: 9);
    operationsLog(operationsTicket($category, 'OPS-VEL-3'), $scanner, minutesAgo: 12);

    $summary = Livewire::test(ScannerOperations::class)->instance()->summary();

    expect($summary['recent'])->toBe(2)
        ->and($summary['totalToday'])->toBe(3);
});

test('last activity', function () {
    $scanner = User::factory()->scanner()->create(['name' => 'Activity Scanner']);
    operationsSession($scanner, seenMinutesAgo: 1);

    $event = operationsEvent();
    $category = operationsCategory($event, 'Regular');

    operationsLog(operationsTicket($category, 'OPS-ACT-OLD'), $scanner, minutesAgo: 5);
    operationsLog(operationsTicket($category, 'OPS-ACT-NEW'), $scanner, minutesAgo: 1);

    $activities = Livewire::test(ScannerOperations::class)->instance()->lastActivities();

    expect($activities)->toHaveCount(2)
        ->and($activities[0]['scanner'])->toBe('Activity Scanner')
        ->and($activities[0]['qr'])->toBe('OPS-ACT-NEW')
        ->and($activities[0]['category'])->toBe('Regular');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.scanners.operations'))
        ->assertOk()
        ->assertSee('OPS-ACT-NEW')
        ->assertSee('Activity Scanner')
        ->assertSee('Regular');
});

test('event isolation', function () {
    $scanner = User::factory()->scanner()->create(['name' => 'Isolated Scanner']);
    operationsSession($scanner, seenMinutesAgo: 1);

    $previousEvent = Event::factory()->create(['name' => 'Previous Event', 'status' => EventStatus::Completed]);
    $activeEvent = operationsEvent('Current Event');

    $previousCategory = operationsCategory($previousEvent, 'Previous');
    $activeCategory = operationsCategory($activeEvent, 'Current');

    operationsLog(operationsTicket($previousCategory, 'OPS-OLD-EVENT'), $scanner, minutesAgo: 1);
    operationsLog(operationsTicket($activeCategory, 'OPS-ACTIVE-EVENT'), $scanner, minutesAgo: 2);

    $component = Livewire::test(ScannerOperations::class);

    expect(CheckInLog::count())->toBe(2)
        ->and($component->instance()->summary()['recent'])->toBe(1)
        ->and($component->instance()->scannerRows()[0]['scans'])->toBe(1)
        ->and($component->instance()->lastActivities())->toHaveCount(1)
        ->and($component->instance()->lastActivities()[0]['qr'])->toBe('OPS-ACTIVE-EVENT');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.scanners.operations'))
        ->assertOk()
        ->assertSee('OPS-ACTIVE-EVENT')
        ->assertDontSee('OPS-OLD-EVENT');
});
