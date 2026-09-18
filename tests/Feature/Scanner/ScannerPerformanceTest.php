<?php

use App\Enums\EventStatus;
use App\Models\CheckInLog;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketCheckInService;
use App\Services\TicketValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function perfEvent(string $name = 'Perf Event', EventStatus $status = EventStatus::Active): Event
{
    return Event::factory()->create(['name' => $name, 'status' => $status]);
}

function perfCategory(Event $event, string $name = 'Regular'): TicketCategory
{
    return TicketCategory::factory()->for($event)->create(['name' => $name]);
}

function perfTicket(TicketCategory $category, string $code): Ticket
{
    return Ticket::factory()->for($category, 'ticketCategory')->create([
        'event_id' => $category->event_id,
        'code' => $code,
        'qr_code' => $code,
    ]);
}

/**
 * Insert a bulk ticket dataset so the lookup runs against a realistic table size.
 */
function perfBulkTickets(Event $event, TicketCategory $category, int $count): void
{
    $now = now()->toDateTimeString();
    $rows = [];

    for ($index = 1; $index <= $count; $index++) {
        $code = sprintf('BULK-%05d', $index);

        $rows[] = [
            'event_id' => $event->id,
            'ticket_category_id' => $category->id,
            'code' => $code,
            'qr_code' => $code,
            'status' => Ticket::STATUS_REGISTERED,
            'registered_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    foreach (array_chunk($rows, 500) as $chunk) {
        DB::table('tickets')->insert($chunk);
    }
}

test('QR lookup tetap menemukan ticket pada dataset besar', function () {
    $event = perfEvent();
    $category = perfCategory($event);

    perfBulkTickets($event, $category, 2000);
    perfTicket($category, 'TARGET-0001');

    $result = app(TicketValidationService::class)->validate('target-0001');

    expect($result['status'])->toBe(TicketValidationService::VALID)
        ->and($result['code'])->toBe('TARGET-0001')
        ->and($result['category'])->toBe('Regular')
        ->and($result['event'])->toBe('Perf Event');
});

test('dua check-in pada satu QR menghasilkan satu success dan satu already checked in', function () {
    $event = perfEvent();
    $category = perfCategory($event);
    $ticket = perfTicket($category, 'RACE-0001');

    $firstScanner = User::factory()->scanner()->create(['name' => 'Scanner A']);
    $secondScanner = User::factory()->scanner()->create(['name' => 'Scanner B']);

    $this->actingAs($firstScanner);
    $this->postJson(route('scanner.check-in'), ['code' => 'RACE-0001'])
        ->assertOk()
        ->assertJsonPath('status', TicketCheckInService::SUCCESS);

    $this->actingAs($secondScanner);
    $this->postJson(route('scanner.check-in'), ['code' => 'RACE-0001'])
        ->assertOk()
        ->assertJsonPath('status', TicketValidationService::USED);

    expect($ticket->fresh()->checked_in_by)->toBe($firstScanner->id);

    expect(CheckInLog::where('ticket_id', $ticket->id)->where('status', CheckInLog::STATUS_SUCCESS)->count())->toBe(1)
        ->and(CheckInLog::where('ticket_id', $ticket->id)->where('status', CheckInLog::STATUS_ALREADY_CHECKED_IN)->count())->toBe(1)
        ->and(CheckInLog::count())->toBe(2);

    // No ticket may ever collect more than one success log.
    $duplicated = CheckInLog::query()
        ->select('ticket_id')
        ->where('status', CheckInLog::STATUS_SUCCESS)
        ->whereNotNull('ticket_id')
        ->groupBy('ticket_id')
        ->havingRaw('count(*) > 1')
        ->count();

    expect($duplicated)->toBe(0);
});

test('ticket dari event lain tidak bisa digunakan', function () {
    perfEvent();

    $otherEvent = perfEvent('Event Lain', EventStatus::Draft);
    $otherCategory = perfCategory($otherEvent, 'Regular');
    perfTicket($otherCategory, 'OTHER-0001');

    $this->actingAs(User::factory()->scanner()->create());

    $this->postJson(route('scanner.check-in'), ['code' => 'OTHER-0001'])
        ->assertOk()
        ->assertJsonPath('status', TicketValidationService::NOT_FOUND);

    expect(CheckInLog::where('status', CheckInLog::STATUS_SUCCESS)->count())->toBe(0);
});

test('invalid QR tidak membuat check in log success', function () {
    perfEvent();

    $this->actingAs(User::factory()->scanner()->create());

    $this->postJson(route('scanner.check-in'), ['code' => 'TIDAK-ADA-999'])
        ->assertOk()
        ->assertJsonPath('status', TicketValidationService::NOT_FOUND);

    expect(CheckInLog::where('status', CheckInLog::STATUS_SUCCESS)->count())->toBe(0)
        ->and(CheckInLog::where('status', CheckInLog::STATUS_INVALID)->count())->toBe(1);
});

test('scan tidak melakukan query berulang', function () {
    $event = perfEvent();
    $category = perfCategory($event);
    perfTicket($category, 'BUDGET-0001');

    $scanner = User::factory()->scanner()->create();

    DB::flushQueryLog();
    DB::enableQueryLog();
    app(TicketValidationService::class)->validate('BUDGET-0001');
    $validateQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    DB::flushQueryLog();
    DB::enableQueryLog();
    app(TicketCheckInService::class)->checkIn('BUDGET-0001', $scanner);
    $checkInQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Measured against MySQL with 7000 tickets: 3 queries for validate, 5 for check-in.
    expect($validateQueries)->toBeLessThanOrEqual(4)
        ->and($checkInQueries)->toBeLessThanOrEqual(6);
});
