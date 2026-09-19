<?php

use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function legacyTicket(Event $event, TicketCategory $category, array $attributes = []): Ticket
{
    return Ticket::factory()
        ->for($category, 'ticketCategory')
        ->create([
            'event_id' => $event->id,
            ...$attributes,
        ]);
}

test('validate tanpa user tetap memakai event aktif', function () {
    $event = Event::factory()->active()->create(['name' => 'Event Aktif']);
    $category = TicketCategory::factory()->for($event)->create(['name' => 'Regular']);
    legacyTicket($event, $category, ['code' => 'LEGACY-001']);

    $result = app(TicketValidationService::class)->validate('legacy-001');

    expect($result['status'])->toBe(TicketValidationService::VALID)
        ->and($result['code'])->toBe('LEGACY-001')
        ->and($result['category'])->toBe('Regular')
        ->and($result['event'])->toBe('Event Aktif')
        ->and($result['message'])->toBe('Tiket aktif dan belum digunakan.');
});

test('validate tanpa user menandai tiket yang sudah dipakai sebagai used', function () {
    $event = Event::factory()->active()->create();
    $category = TicketCategory::factory()->for($event)->create();
    legacyTicket($event, $category, [
        'code' => 'LEGACY-USED',
        'checked_in_at' => now(),
        'checked_in_by' => User::factory(),
    ]);

    $result = app(TicketValidationService::class)->validate('LEGACY-USED');

    expect($result['status'])->toBe(TicketValidationService::USED)
        ->and($result['message'])->toBe('Tiket ini sudah digunakan sebelumnya.');
});

test('validate tanpa user mengembalikan not_found untuk tiket di luar event aktif', function () {
    $inactive = Event::factory()->completed()->create();
    $category = TicketCategory::factory()->for($inactive)->create();
    legacyTicket($inactive, $category, ['code' => 'LEGACY-OLD']);

    $result = app(TicketValidationService::class)->validate('LEGACY-OLD');

    expect($result['status'])->toBe(TicketValidationService::NOT_FOUND)
        ->and($result['message'])->toBe('QR ini tidak terdaftar pada event aktif.')
        ->and($result['category'])->toBeNull()
        ->and($result['event'])->toBeNull();
});

test('validate tanpa user mengembalikan not_found untuk kode tak dikenal', function () {
    Event::factory()->active()->create();

    $result = app(TicketValidationService::class)->validate('TIDAK-ADA');

    expect($result['status'])->toBe(TicketValidationService::NOT_FOUND);
});

test('validate dengan admin tetap memakai event aktif', function () {
    $admin = User::factory()->admin()->create();

    $event = Event::factory()->active()->create();
    $category = TicketCategory::factory()->for($event)->create();
    legacyTicket($event, $category, ['code' => 'ADMIN-LEGACY']);

    $result = app(TicketValidationService::class)->validate('admin-legacy', $admin);

    expect($result['status'])->toBe(TicketValidationService::VALID)
        ->and($result['code'])->toBe('ADMIN-LEGACY');
});

test('payload tanpa message memakai pesan default', function () {
    $payload = app(TicketValidationService::class)->payload(TicketValidationService::VALID, 'ABC-001');

    expect($payload['status'])->toBe(TicketValidationService::VALID)
        ->and($payload['code'])->toBe('ABC-001')
        ->and($payload['message'])->toBe('Tiket aktif dan belum digunakan.')
        ->and($payload['category'])->toBeNull()
        ->and($payload['checked_in_at'])->toBeNull()
        ->and($payload['gate'])->toBeNull();
});

test('payload tetap menerima scanner sebagai argumen keempat', function () {
    $scanner = User::factory()->scanner()->create(['name' => 'Andi Legacy']);

    $payload = app(TicketValidationService::class)->payload(TicketValidationService::VALID, 'ABC-002', null, $scanner);

    expect($payload['scanner'])->toBe('Andi Legacy');
});
