<?php

use App\Models\CheckInLog;
use App\Models\Event;
use App\Models\ScannerEventAssignment;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function uxEvent(string $name = 'Festival ABC 2026'): Event
{
    return Event::factory()->active()->create(['name' => $name]);
}

function uxCategory(Event $event, string $name = 'VIP'): TicketCategory
{
    return TicketCategory::factory()->for($event)->create(['name' => $name]);
}

function uxTicket(TicketCategory $category, string $code): Ticket
{
    return Ticket::factory()->for($category, 'ticketCategory')->create([
        'event_id' => $category->event_id,
        'code' => $code,
        'qr_code' => $code,
    ]);
}

test('valid QR menampilkan status success', function () {
    $scanner = User::factory()->scanner()->create(['name' => 'Andi']);

    $event = uxEvent();
    uxTicket(uxCategory($event), 'UX-001');
    ScannerEventAssignment::create(['user_id' => $scanner->id, 'event_id' => $event->id, 'assigned_at' => now()]);
    $this->actingAs($scanner);

    $this->postJson(route('scanner.check-in'), ['code' => 'ux-001'])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('code', 'UX-001')
        ->assertJsonPath('message', 'Check-in berhasil. Siap untuk tiket berikutnya.');
});

test('duplicate QR menampilkan already checked in', function () {
    $event = uxEvent();
    uxTicket(uxCategory($event), 'UX-002');

    $andi = User::factory()->scanner()->create(['name' => 'Andi']);
    $budi = User::factory()->scanner()->create(['name' => 'Budi']);
    ScannerEventAssignment::create(['user_id' => $andi->id, 'event_id' => $event->id, 'assigned_at' => now()]);
    ScannerEventAssignment::create(['user_id' => $budi->id, 'event_id' => $event->id, 'assigned_at' => now()]);

    $this->actingAs($andi);
    $this->postJson(route('scanner.check-in'), ['code' => 'UX-002'])->assertOk();

    $this->actingAs($budi);

    $this->postJson(route('scanner.check-in'), ['code' => 'UX-002'])
        ->assertOk()
        ->assertJsonPath('status', 'used')
        ->assertJsonPath('category', 'VIP')
        ->assertJsonPath('scanner', 'Andi')
        ->assertJsonPath('message', 'Tiket ini sudah digunakan sebelumnya.');

    expect(CheckInLog::where('status', CheckInLog::STATUS_ALREADY_CHECKED_IN)->count())->toBe(1);
});

test('invalid QR menampilkan status invalid', function () {
    $scanner = User::factory()->scanner()->create();

    $event = uxEvent();
    ScannerEventAssignment::create(['user_id' => $scanner->id, 'event_id' => $event->id, 'assigned_at' => now()]);
    $this->actingAs($scanner);

    $this->postJson(route('scanner.check-in'), ['code' => 'TIDAK-ADA'])
        ->assertOk()
        ->assertJsonPath('status', 'not_found')
        ->assertJsonPath('category', null)
        ->assertJsonPath('message', 'QR ini tidak terdaftar pada event aktif.');

    expect(CheckInLog::where('status', CheckInLog::STATUS_INVALID)->count())->toBe(1);
});

test('response membawa kategori scanner dan waktu', function () {
    $scanner = User::factory()->scanner()->create(['name' => 'Andi Pratama']);

    $event = uxEvent();
    uxTicket(uxCategory($event, 'VVIP'), 'UX-004');
    ScannerEventAssignment::create(['user_id' => $scanner->id, 'event_id' => $event->id, 'assigned_at' => now()]);
    $this->actingAs($scanner);

    $response = $this->postJson(route('scanner.check-in'), ['code' => 'UX-004'])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('category', 'VVIP')
        ->assertJsonPath('scanner', 'Andi Pratama')
        ->assertJsonPath('event', 'Festival ABC 2026');

    expect($response->json('checked_in_date'))->not->toBeNull()
        ->and($response->json('checked_in_time'))->not->toBeNull()
        ->and($response->json('checked_in_at'))->not->toBeNull();
});

test('scanner nonaktif tetap ditolak', function () {
    uxTicket(uxCategory(uxEvent()), 'UX-005');

    $this->actingAs(User::factory()->scanner()->create(['is_active' => false]))
        ->postJson(route('scanner.check-in'), ['code' => 'UX-005'])
        ->assertForbidden();

    expect(CheckInLog::count())->toBe(0);
});

test('halaman scanner menampilkan badge status dan tombol scan berikutnya', function () {
    $event = uxEvent();
    $scanner = User::factory()->scanner()->create();
    ScannerEventAssignment::create(['user_id' => $scanner->id, 'event_id' => $event->id, 'assigned_at' => now()]);

    $this->actingAs($scanner)
        ->get(route('scanner.index'))
        ->assertOk()
        ->assertSee('success-status-badge', false)
        ->assertSee('BERHASIL')
        ->assertSee('used-status-badge', false)
        ->assertSee('SUDAH DIGUNAKAN')
        ->assertSee('invalid-status-badge', false)
        ->assertSee('TIKET TIDAK VALID')
        ->assertSee('success-ticket-code', false)
        ->assertSee('used-ticket-code', false)
        ->assertSee('scan-next-success-button', false)
        ->assertSee('scan-next-used-button', false)
        ->assertSee('Scan Tiket Berikutnya');
});
