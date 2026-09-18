<?php

use App\Models\CheckInLog;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketCheckInService;
use App\Services\TicketValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function checkInHistoryActiveTicket(array $attributes = []): Ticket
{
    $event = Event::factory()->active()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => $attributes['category_name'] ?? 'VIP']);

    unset($attributes['category_name']);

    return Ticket::factory()->for($category, 'ticketCategory')->create($attributes);
}

test('guest tidak bisa akses check in history', function () {
    $this->get(route('admin.check-in-history'))->assertRedirect(route('login'));
});

test('scanner forbidden dan admin bisa akses check in history', function () {
    $scanner = User::factory()->scanner()->create();

    $this->actingAs($scanner)
        ->get(route('admin.check-in-history'))
        ->assertForbidden();

    $admin = User::factory()->admin()->create();
    $ticket = checkInHistoryActiveTicket(['code' => 'HIS-001', 'category_name' => 'Regular']);

    CheckInLog::create([
        'ticket_id' => $ticket->id,
        'scanner_id' => $scanner->id,
        'qr_code' => $ticket->qr_code,
        'status' => CheckInLog::STATUS_SUCCESS,
        'scanned_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.check-in-history'))
        ->assertOk()
        ->assertSee('Riwayat Check-in')
        ->assertSee('HIS-001')
        ->assertSee('Regular')
        ->assertSee($scanner->name)
        ->assertSee('Success');
});

test('successful check in membuat log', function () {
    $scanner = User::factory()->scanner()->create();
    $this->actingAs($scanner);

    $ticket = checkInHistoryActiveTicket(['code' => 'LOG-001']);

    $this->postJson(route('scanner.check-in'), ['code' => 'log-001'])
        ->assertOk()
        ->assertJson(['status' => TicketCheckInService::SUCCESS]);

    $this->assertDatabaseHas('check_in_logs', [
        'ticket_id' => $ticket->id,
        'scanner_id' => $scanner->id,
        'qr_code' => 'LOG-001',
        'status' => CheckInLog::STATUS_SUCCESS,
    ]);
});

test('duplicate scan membuat log already checked in', function () {
    $scanner = User::factory()->scanner()->create();
    $this->actingAs($scanner);

    $ticket = checkInHistoryActiveTicket(['code' => 'LOG-002']);

    $this->postJson(route('scanner.check-in'), ['code' => 'LOG-002'])
        ->assertOk()
        ->assertJson(['status' => TicketCheckInService::SUCCESS]);

    $firstCheckedInAt = $ticket->fresh()->checked_in_at;

    $this->postJson(route('scanner.check-in'), ['code' => 'LOG-002'])
        ->assertOk()
        ->assertJson(['status' => TicketValidationService::USED]);

    expect($ticket->fresh()->checked_in_at->equalTo($firstCheckedInAt))->toBeTrue()
        ->and(CheckInLog::where('ticket_id', $ticket->id)->count())->toBe(2);

    $this->assertDatabaseHas('check_in_logs', [
        'ticket_id' => $ticket->id,
        'scanner_id' => $scanner->id,
        'qr_code' => 'LOG-002',
        'status' => CheckInLog::STATUS_ALREADY_CHECKED_IN,
    ]);
});

test('invalid QR membuat log invalid', function () {
    $scanner = User::factory()->scanner()->create();
    $this->actingAs($scanner);

    $this->postJson(route('scanner.check-in'), ['code' => 'missing'])
        ->assertOk()
        ->assertJson(['status' => TicketValidationService::NOT_FOUND]);

    $this->assertDatabaseHas('check_in_logs', [
        'ticket_id' => null,
        'scanner_id' => $scanner->id,
        'qr_code' => 'MISSING',
        'status' => CheckInLog::STATUS_INVALID,
    ]);
});
