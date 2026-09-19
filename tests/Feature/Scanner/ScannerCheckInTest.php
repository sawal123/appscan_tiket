<?php

use App\Models\Event;
use App\Models\ScannerEventAssignment;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketCheckInService;
use App\Services\TicketValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function activeTicket(array $attributes = []): Ticket
{
    $event = Event::factory()->active()->create();
    $category = TicketCategory::factory()->for($event)->create();

    return Ticket::factory()->for($category, 'ticketCategory')->create($attributes);
}

function assignCheckInScanner(User $scanner, Event $event): void
{
    ScannerEventAssignment::create([
        'user_id' => $scanner->id,
        'event_id' => $event->id,
        'assigned_at' => now(),
    ]);
}

test('guests are redirected to login from the scanner pages', function () {
    $this->get(route('scanner.index'))->assertRedirect(route('login'));
    $this->get(route('scanner.verified'))->assertRedirect(route('login'));
});

test('scanner role can open the scanner and verified pages', function () {
    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('scanner.index'))
        ->assertOk()
        ->assertSee('TICKET SCANNER');

    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('scanner.verified'))
        ->assertOk()
        ->assertSee('Tiket Terverifikasi');
});

test('admin can open the scanner page', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('scanner.index'))
        ->assertOk();
});

test('login page renders the sliced scanner design', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('TICKET SCANNER')
        ->assertSee('Siap menjaga alur masuk tetap lancar.');
});

test('validation returns valid for an unused ticket on the active event', function () {
    $scanner = User::factory()->scanner()->create();

    $ticket = activeTicket(['code' => 'VIP-001']);
    assignCheckInScanner($scanner, $ticket->event);
    $this->actingAs($scanner);

    $this->postJson(route('scanner.validate'), ['code' => 'vip-001'])
        ->assertOk()
        ->assertJson([
            'status' => TicketValidationService::VALID,
            'code' => 'VIP-001',
            'category' => $ticket->ticketCategory->name,
        ]);
});

test('validation returns used for a ticket that was already checked in', function () {
    $scanner = User::factory()->scanner()->create();

    $ticket = activeTicket(['code' => 'VIP-002', 'checked_in_at' => now(), 'checked_in_by' => User::factory()]);
    assignCheckInScanner($scanner, $ticket->event);
    $this->actingAs($scanner);

    $this->postJson(route('scanner.validate'), ['code' => 'VIP-002'])
        ->assertOk()
        ->assertJson(['status' => TicketValidationService::USED]);
});

test('validation returns not found for an unknown code', function () {
    $scanner = User::factory()->scanner()->create();

    $ticket = activeTicket(['code' => 'VIP-003']);
    assignCheckInScanner($scanner, $ticket->event);
    $this->actingAs($scanner);

    $this->postJson(route('scanner.validate'), ['code' => 'NOPE-999'])
        ->assertOk()
        ->assertJson(['status' => TicketValidationService::NOT_FOUND]);
});

test('validation rejects tickets outside scanner assigned event', function () {
    $scanner = User::factory()->scanner()->create();
    $activeTicket = activeTicket(['code' => 'ACTIVE-001']);
    $event = Event::factory()->completed()->create();
    $category = TicketCategory::factory()->for($event)->create();
    Ticket::factory()->for($category, 'ticketCategory')->create(['code' => 'OLD-001']);
    assignCheckInScanner($scanner, $activeTicket->event);
    $this->actingAs($scanner);

    $this->postJson(route('scanner.validate'), ['code' => 'OLD-001'])
        ->assertOk()
        ->assertJson(['status' => TicketValidationService::INVALID]);
});

test('check in stores the check in time and the operator', function () {
    $scanner = User::factory()->scanner()->create();

    $ticket = activeTicket(['code' => 'REG-001']);
    assignCheckInScanner($scanner, $ticket->event);
    $this->actingAs($scanner);

    $this->postJson(route('scanner.check-in'), ['code' => 'REG-001'])
        ->assertOk()
        ->assertJson([
            'status' => TicketCheckInService::SUCCESS,
            'scanner' => $scanner->name,
        ]);

    $ticket->refresh();

    expect($ticket->checked_in_at)->not->toBeNull()
        ->and($ticket->checked_in_by)->toBe($scanner->id);
});

test('checking the same ticket in twice does not duplicate the check in', function () {
    $scanner = User::factory()->scanner()->create();

    $ticket = activeTicket(['code' => 'VIP-004']);
    assignCheckInScanner($scanner, $ticket->event);
    $this->actingAs($scanner);

    $this->postJson(route('scanner.check-in'), ['code' => 'VIP-004'])
        ->assertOk()
        ->assertJson(['status' => TicketCheckInService::SUCCESS]);

    $firstCheckedInAt = $ticket->fresh()->checked_in_at;

    $this->postJson(route('scanner.check-in'), ['code' => 'VIP-004'])
        ->assertOk()
        ->assertJson(['status' => TicketValidationService::USED]);

    expect($ticket->fresh()->checked_in_at->equalTo($firstCheckedInAt))->toBeTrue();
});

test('check in returns not found for an unknown code', function () {
    $scanner = User::factory()->scanner()->create();
    $ticket = activeTicket(['code' => 'KNOWN-001']);
    assignCheckInScanner($scanner, $ticket->event);
    $this->actingAs($scanner);

    $this->postJson(route('scanner.check-in'), ['code' => 'MISSING'])
        ->assertOk()
        ->assertJson(['status' => TicketValidationService::NOT_FOUND]);
});

test('validate and check in require a code', function () {
    $this->actingAs(User::factory()->scanner()->create());

    $this->postJson(route('scanner.validate'), ['code' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors('code');

    $this->postJson(route('scanner.check-in'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('code');
});

test('guests cannot validate or check in', function () {
    $this->postJson(route('scanner.validate'), ['code' => 'VIP-001'])->assertUnauthorized();
    $this->postJson(route('scanner.check-in'), ['code' => 'VIP-001'])->assertUnauthorized();
});

test('verified page lists tickets that were checked in on the active event', function () {
    $scanner = User::factory()->scanner()->create();

    $ticket = activeTicket([
        'code' => 'VIP-777',
        'checked_in_at' => now(),
        'checked_in_by' => User::factory(),
    ]);
    assignCheckInScanner($scanner, $ticket->event);
    $this->actingAs($scanner);

    $this->get(route('scanner.verified'))
        ->assertOk()
        ->assertSee('VIP-777');
});
