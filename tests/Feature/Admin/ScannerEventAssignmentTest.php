<?php

use App\Livewire\Admin\ScannerCreate;
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
use RuntimeException;
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

test('tambah scanner wajib memilih event', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ScannerCreate::class)
        ->set('name', 'Scanner Gate 01')
        ->set('email', 'scanner.required@gateflow.test')
        ->set('password', 'rahasia123')
        ->set('password_confirmation', 'rahasia123')
        ->call('save')
        ->assertHasErrors(['event_id' => 'required']);

    expect(User::query()->where('email', 'scanner.required@gateflow.test')->exists())->toBeFalse();
});

test('scanner baru otomatis memiliki assignment event', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->active()->create(['name' => 'Festival Musik 2026']);

    $this->actingAs($admin);

    Livewire::test(ScannerCreate::class)
        ->set('name', 'Scanner Gate 01')
        ->set('email', 'scanner.auto@gateflow.test')
        ->set('password', 'rahasia123')
        ->set('password_confirmation', 'rahasia123')
        ->set('event_id', $event->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.scanners'));

    $scanner = User::query()->where('email', 'scanner.auto@gateflow.test')->sole();

    expect(ScannerEventAssignment::query()
        ->where('user_id', $scanner->id)
        ->where('event_id', $event->id)
        ->where('assigned_by', $admin->id)
        ->exists())->toBeTrue();
});

test('tambah scanner dengan event draft berhasil', function () {
    $this->actingAs(User::factory()->admin()->create());
    $draftEvent = Event::factory()->create();

    Livewire::test(ScannerCreate::class)
        ->set('name', 'Scanner Draft')
        ->set('email', 'scanner.draft@gateflow.test')
        ->set('password', 'rahasia123')
        ->set('password_confirmation', 'rahasia123')
        ->set('event_id', $draftEvent->id)
        ->call('save')
        ->assertHasNoErrors();

    $scanner = User::query()->where('email', 'scanner.draft@gateflow.test')->sole();

    expect(ScannerEventAssignment::query()->where('user_id', $scanner->id)->where('event_id', $draftEvent->id)->exists())->toBeTrue();
});

test('tambah scanner dengan event active berhasil', function () {
    $this->actingAs(User::factory()->admin()->create());
    $activeEvent = Event::factory()->active()->create();

    Livewire::test(ScannerCreate::class)
        ->set('name', 'Scanner Active')
        ->set('email', 'scanner.active@gateflow.test')
        ->set('password', 'rahasia123')
        ->set('password_confirmation', 'rahasia123')
        ->set('event_id', $activeEvent->id)
        ->call('save')
        ->assertHasNoErrors();

    $scanner = User::query()->where('email', 'scanner.active@gateflow.test')->sole();

    expect(ScannerEventAssignment::query()->where('user_id', $scanner->id)->where('event_id', $activeEvent->id)->exists())->toBeTrue();
});

test('event completed tidak tersedia dan tidak dapat dipilih saat tambah scanner', function () {
    $this->actingAs(User::factory()->admin()->create());
    $draftEvent = Event::factory()->create(['name' => 'Event Draft']);
    $completedEvent = Event::factory()->completed()->create(['name' => 'Event Selesai']);

    Livewire::test(ScannerCreate::class)
        ->assertSee('Event Draft')
        ->assertDontSee('Event Selesai')
        ->set('name', 'Scanner Completed')
        ->set('email', 'scanner.completed@gateflow.test')
        ->set('password', 'rahasia123')
        ->set('password_confirmation', 'rahasia123')
        ->set('event_id', $completedEvent->id)
        ->call('save')
        ->assertHasErrors('event_id');

    expect(User::query()->where('email', 'scanner.completed@gateflow.test')->exists())->toBeFalse()
        ->and(ScannerEventAssignment::query()->where('event_id', $draftEvent->id)->exists())->toBeFalse();
});

test('jika assignment gagal user tidak tersimpan', function () {
    $this->actingAs(User::factory()->admin()->create());
    $event = Event::factory()->active()->create();

    ScannerEventAssignment::creating(function (): void {
        throw new RuntimeException('Assignment gagal.');
    });

    try {
        expect(fn () => Livewire::test(ScannerCreate::class)
            ->set('name', 'Scanner Rollback')
            ->set('email', 'scanner.rollback@gateflow.test')
            ->set('password', 'rahasia123')
            ->set('password_confirmation', 'rahasia123')
            ->set('event_id', $event->id)
            ->call('save'))->toThrow(RuntimeException::class);
    } finally {
        ScannerEventAssignment::flushEventListeners();
    }

    expect(User::query()->where('email', 'scanner.rollback@gateflow.test')->exists())->toBeFalse();
});

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

test('scanner dengan event draft tidak dapat melakukan check-in', function () {
    $scanner = User::factory()->scanner()->create(['name' => 'Scanner Draft']);
    $event = Event::factory()->create(['name' => 'Event Draft']);
    assignScannerToEvent($scanner, $event);
    assignmentTicket($event, 'DRAFT-001');

    $this->actingAs($scanner)
        ->get(route('scanner.index'))
        ->assertOk()
        ->assertDontSee('camera-mode-panel', false)
        ->assertDontSee('hardware-scanner-input', false);

    $this->postJson(route('scanner.check-in'), ['code' => 'DRAFT-001'])
        ->assertOk()
        ->assertJsonPath('status', TicketValidationService::INVALID);
});

test('scanner dengan event active dapat melakukan check-in', function () {
    $scanner = User::factory()->scanner()->create(['name' => 'Scanner Active']);
    $event = Event::factory()->active()->create(['name' => 'Event Active']);
    assignScannerToEvent($scanner, $event);
    assignmentTicket($event, 'ACTIVE-001');

    $this->actingAs($scanner)
        ->postJson(route('scanner.check-in'), ['code' => 'ACTIVE-001'])
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

test('scanner tidak dapat di-assign ke event completed', function () {
    $admin = User::factory()->admin()->create();
    $scanner = User::factory()->scanner()->create();
    $completedEvent = Event::factory()->completed()->create(['name' => 'Event Sudah Selesai']);

    Livewire::actingAs($admin)
        ->test(Scanners::class)
        ->call('assignEvent', $scanner->id)
        ->set('assignmentEventId', $completedEvent->id)
        ->call('saveEventAssignment')
        ->assertHasErrors('assignmentEventId');

    expect(ScannerEventAssignment::query()->where('user_id', $scanner->id)->exists())->toBeFalse();

    $this->actingAs($admin)
        ->get(route('admin.scanners'))
        ->assertOk()
        ->assertDontSee('Event Sudah Selesai');
});

test('scanner dapat di-assign ke event draft dan aktif', function () {
    $admin = User::factory()->admin()->create();

    $draftEvent = Event::factory()->create(['name' => 'Event Draft']);
    $activeEvent = Event::factory()->active()->create(['name' => 'Event Aktif']);

    $draftScanner = User::factory()->scanner()->create();
    $activeScanner = User::factory()->scanner()->create();

    Livewire::actingAs($admin)
        ->test(Scanners::class)
        ->call('assignEvent', $draftScanner->id)
        ->set('assignmentEventId', $draftEvent->id)
        ->call('saveEventAssignment')
        ->assertHasNoErrors();

    Livewire::actingAs($admin)
        ->test(Scanners::class)
        ->call('assignEvent', $activeScanner->id)
        ->set('assignmentEventId', $activeEvent->id)
        ->call('saveEventAssignment')
        ->assertHasNoErrors();

    expect(ScannerEventAssignment::query()->where('user_id', $draftScanner->id)->where('event_id', $draftEvent->id)->exists())->toBeTrue()
        ->and(ScannerEventAssignment::query()->where('user_id', $activeScanner->id)->where('event_id', $activeEvent->id)->exists())->toBeTrue();
});
