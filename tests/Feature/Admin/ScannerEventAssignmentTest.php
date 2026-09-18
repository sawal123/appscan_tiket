<?php

use App\Livewire\Admin\Scanners;
use App\Models\CheckInLog;
use App\Models\Event;
use App\Models\ScannerEventAssignment;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketCheckInService;
use App\Services\TicketValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function assignmentTicket(Event $event, string $code): Ticket
{
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);

    return Ticket::factory()->for($category, 'ticketCategory')->create([
        'event_id' => $event->id,
        'code' => $code,
        'qr_code' => $code,
    ]);
}

function assignScannerToEvent(User $scanner, Event $event, ?User $admin = null): ScannerEventAssignment
{
    return ScannerEventAssignment::query()->create([
        'user_id' => $scanner->id,
        'event_id' => $event->id,
        'assigned_by' => $admin?->id,
        'assigned_at' => now(),
    ]);
}

test('admin dapat assign scanner ke event', function () {
    $admin = User::factory()->admin()->create();
    $scanner = User::factory()->scanner()->create(['name' => 'Scanner Gate 01']);
    $event = Event::factory()->active()->create(['name' => 'Festival Musik 2026']);

    Livewire::actingAs($admin)
        ->test(Scanners::class)
        ->call('assignEvent', $scanner->id)
        ->assertSet('assigningScannerName', 'Scanner Gate 01')
        ->set('assignmentEventId', $event->id)
        ->call('saveEventAssignment')
        ->assertHasNoErrors();

    expect(ScannerEventAssignment::query()->where('user_id', $scanner->id)->where('event_id', $event->id)->exists())->toBeTrue();

    $this->actingAs($admin)
        ->get(route('admin.scanners'))
        ->assertOk()
        ->assertSee('Festival Musik 2026');
});

test('scanner hanya memiliki satu assignment', function () {
    $admin = User::factory()->admin()->create();
    $scanner = User::factory()->scanner()->create();
    $event = Event::factory()->active()->create();

    Livewire::actingAs($admin)
        ->test(Scanners::class)
        ->call('assignEvent', $scanner->id)
        ->set('assignmentEventId', $event->id)
        ->call('saveEventAssignment');

    Livewire::actingAs($admin)
        ->test(Scanners::class)
        ->call('assignEvent', $scanner->id)
        ->set('assignmentEventId', $event->id)
        ->call('saveEventAssignment');

    expect(ScannerEventAssignment::query()->where('user_id', $scanner->id)->count())->toBe(1);
});

test('mengganti event mengganti assignment lama', function () {
    $admin = User::factory()->admin()->create();
    $scanner = User::factory()->scanner()->create();
    $firstEvent = Event::factory()->active()->create(['name' => 'Event A']);
    $secondEvent = Event::factory()->active()->create(['name' => 'Event B']);

    assignScannerToEvent($scanner, $firstEvent, $admin);

    Livewire::actingAs($admin)
        ->test(Scanners::class)
        ->call('assignEvent', $scanner->id)
        ->set('assignmentEventId', $secondEvent->id)
        ->call('saveEventAssignment');

    expect(ScannerEventAssignment::query()->where('user_id', $scanner->id)->count())->toBe(1)
        ->and($scanner->scannerEventAssignment()->first()?->event_id)->toBe($secondEvent->id);
});

test('scanner tanpa assignment tidak dapat scan', function () {
    $scanner = User::factory()->scanner()->create();
    $event = Event::factory()->active()->create();
    assignmentTicket($event, 'NO-ASSIGN-001');

    $this->actingAs($scanner)
        ->get(route('scanner.index'))
        ->assertOk()
        ->assertSee('Scanner belum memiliki event. Hubungi administrator.')
        ->assertDontSee('camera-mode-panel', false)
        ->assertDontSee('hardware-scanner-input', false)
        ->assertDontSee('manual-qr-input', false);

    $this->postJson(route('scanner.validate'), ['code' => 'NO-ASSIGN-001'])
        ->assertOk()
        ->assertJsonPath('status', TicketValidationService::INVALID)
        ->assertJsonPath('message', 'Scanner belum memiliki event. Hubungi administrator.');

    $this->postJson(route('scanner.check-in'), ['code' => 'NO-ASSIGN-001'])
        ->assertOk()
        ->assertJsonPath('status', TicketValidationService::INVALID);
});

test('scanner dengan assignment dapat scan', function () {
    $scanner = User::factory()->scanner()->create(['name' => 'Scanner Gate 01']);
    $event = Event::factory()->active()->create(['name' => 'Festival Musik 2026']);
    assignScannerToEvent($scanner, $event);
    assignmentTicket($event, 'ASSIGN-001');

    $this->actingAs($scanner)
        ->get(route('scanner.index'))
        ->assertOk()
        ->assertSee('Scanner Gate 01')
        ->assertSee('Festival Musik 2026')
        ->assertSee('Event Assigned')
        ->assertSee('camera-mode-panel', false)
        ->assertSee('hardware-scanner-input', false);

    $this->postJson(route('scanner.check-in'), ['code' => 'ASSIGN-001'])
        ->assertOk()
        ->assertJsonPath('status', TicketCheckInService::SUCCESS);
});

test('scanner hanya dapat scan ticket pada event assignment', function () {
    $scanner = User::factory()->scanner()->create();
    $eventA = Event::factory()->active()->create(['name' => 'Event A']);
    $eventB = Event::factory()->active()->create(['name' => 'Event B']);
    assignScannerToEvent($scanner, $eventA);

    $ticketA = assignmentTicket($eventA, 'EVENT-A-001');
    $ticketB = assignmentTicket($eventB, 'EVENT-B-001');

    $this->actingAs($scanner);

    $this->postJson(route('scanner.check-in'), ['code' => $ticketA->qr_code])
        ->assertOk()
        ->assertJsonPath('status', TicketCheckInService::SUCCESS);

    $this->postJson(route('scanner.check-in'), ['code' => $ticketB->qr_code])
        ->assertOk()
        ->assertJsonPath('status', TicketValidationService::INVALID)
        ->assertJsonPath('message', 'Tiket bukan untuk event scanner ini.');

    expect(CheckInLog::query()->where('ticket_id', $ticketB->id)->where('status', CheckInLog::STATUS_SUCCESS)->exists())->toBeFalse()
        ->and($ticketB->fresh()->checked_in_at)->toBeNull();
});
