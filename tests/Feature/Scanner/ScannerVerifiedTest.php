<?php

use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketCheckInService;
use App\Services\TicketValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function scannerVerifiedTicket(Event $event, TicketCategory $category, array $attributes = []): Ticket
{
    return Ticket::factory()
        ->for($category, 'ticketCategory')
        ->create([
            'event_id' => $event->id,
            ...$attributes,
        ]);
}

test('guest tidak bisa membuka scanner verified', function () {
    $this->get(route('scanner.verified'))->assertRedirect(route('login'));
});

test('scanner dapat membuka verified', function () {
    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('scanner.verified'))
        ->assertOk()
        ->assertSee('Tiket Terverifikasi');
});

test('admin dapat membuka verified', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('scanner.verified'))
        ->assertOk()
        ->assertSee('Tiket Terverifikasi');
});

test('verified hanya menampilkan ticket checked in', function () {
    $scanner = User::factory()->scanner()->create();
    $this->actingAs($scanner);

    $event = Event::factory()->active()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);

    scannerVerifiedTicket($event, $category, [
        'code' => 'SHOW-001',
        'checked_in_at' => now(),
        'checked_in_by' => $scanner->id,
    ]);

    scannerVerifiedTicket($event, $category, ['code' => 'HIDE-001']);

    $this->get(route('scanner.verified'))
        ->assertOk()
        ->assertSee('SHOW-001')
        ->assertDontSee('HIDE-001');
});

test('search QR bekerja', function () {
    $scanner = User::factory()->scanner()->create();
    $this->actingAs($scanner);

    $event = Event::factory()->active()->create();
    $category = TicketCategory::factory()->for($event)->create();

    scannerVerifiedTicket($event, $category, [
        'code' => 'ABC001',
        'checked_in_at' => now(),
        'checked_in_by' => $scanner->id,
    ]);

    scannerVerifiedTicket($event, $category, [
        'code' => 'XYZ002',
        'checked_in_at' => now()->subMinute(),
        'checked_in_by' => $scanner->id,
    ]);

    $this->get(route('scanner.verified', ['search' => 'ABC001']))
        ->assertOk()
        ->assertSee('ABC001')
        ->assertDontSee('XYZ002');
});

test('filter kategori bekerja', function () {
    $scanner = User::factory()->scanner()->create();
    $this->actingAs($scanner);

    $event = Event::factory()->active()->create();
    $vip = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $regular = TicketCategory::factory()->for($event)->create(['name' => 'Regular']);

    scannerVerifiedTicket($event, $vip, [
        'code' => 'VIP-100',
        'checked_in_at' => now(),
        'checked_in_by' => $scanner->id,
    ]);

    scannerVerifiedTicket($event, $regular, [
        'code' => 'REG-100',
        'checked_in_at' => now()->subMinute(),
        'checked_in_by' => $scanner->id,
    ]);

    $this->get(route('scanner.verified', ['category' => $vip->id]))
        ->assertOk()
        ->assertSee('VIP-100')
        ->assertDontSee('REG-100');
});

test('pagination bekerja', function () {
    $scanner = User::factory()->scanner()->create();
    $this->actingAs($scanner);

    $event = Event::factory()->active()->create();
    $category = TicketCategory::factory()->for($event)->create();

    foreach (range(1, 16) as $index) {
        scannerVerifiedTicket($event, $category, [
            'code' => sprintf('PG-%03d', $index),
            'checked_in_at' => now()->subMinutes($index),
            'checked_in_by' => $scanner->id,
        ]);
    }

    $this->get(route('scanner.verified'))
        ->assertOk()
        ->assertSee('PG-001')
        ->assertDontSee('PG-016')
        ->assertSee('Berikutnya');

    $this->get(route('scanner.verified', ['page' => 2]))
        ->assertOk()
        ->assertSee('PG-016')
        ->assertDontSee('PG-001');
});

test('ticket belum check in tidak muncul', function () {
    $this->actingAs(User::factory()->scanner()->create());

    $event = Event::factory()->active()->create();
    $category = TicketCategory::factory()->for($event)->create();

    scannerVerifiedTicket($event, $category, ['code' => 'WAIT-001']);

    $this->get(route('scanner.verified'))
        ->assertOk()
        ->assertDontSee('WAIT-001');
});

test('duplicate scan tetap menampilkan status used', function () {
    $scanner = User::factory()->scanner()->create();
    $this->actingAs($scanner);

    $event = Event::factory()->active()->create();
    $category = TicketCategory::factory()->for($event)->create();
    scannerVerifiedTicket($event, $category, ['code' => 'DUP-001']);

    $this->postJson(route('scanner.check-in'), ['code' => 'DUP-001'])
        ->assertOk()
        ->assertJson(['status' => TicketCheckInService::SUCCESS]);

    $this->postJson(route('scanner.check-in'), ['code' => 'DUP-001'])
        ->assertOk()
        ->assertJson([
            'status' => TicketValidationService::USED,
            'scanner' => $scanner->name,
        ]);
});
