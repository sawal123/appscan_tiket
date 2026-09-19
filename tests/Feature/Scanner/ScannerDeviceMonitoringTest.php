<?php

use App\Models\Event;
use App\Models\ScannerEventAssignment;
use App\Models\ScannerSession;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketCheckInService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function monitoringTicket(string $code = 'MON-001'): Ticket
{
    $event = Event::factory()->active()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);

    return Ticket::factory()->for($category, 'ticketCategory')->create([
        'event_id' => $event->id,
        'code' => $code,
        'qr_code' => $code,
    ]);
}

test('scanner heartbeat membuat session', function () {
    $scanner = User::factory()->scanner()->create();

    $this->actingAs($scanner)
        ->postJson(route('scanner.heartbeat'), [
            'device_id' => 'device-001',
            'device_name' => 'Chrome Scanner',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'ok');

    $this->assertDatabaseHas('scanner_sessions', [
        'user_id' => $scanner->id,
        'device_id' => 'device-001',
        'device_name' => 'Chrome Scanner',
    ]);
});

test('heartbeat update session lama', function () {
    $scanner = User::factory()->scanner()->create();

    $session = ScannerSession::create([
        'user_id' => $scanner->id,
        'device_id' => 'device-002',
        'device_name' => 'Old Device',
        'last_seen_at' => now()->subMinutes(5),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Old Agent',
    ]);

    $this->actingAs($scanner)
        ->postJson(route('scanner.heartbeat'), [
            'device_id' => 'device-002',
            'device_name' => 'Updated Device',
        ])
        ->assertOk()
        ->assertJsonPath('session_id', $session->id);

    expect($session->fresh())
        ->device_name->toBe('Updated Device')
        ->last_seen_at->toBeGreaterThan(now()->subMinute());
});

test('duplicate session tidak dibuat', function () {
    $scanner = User::factory()->scanner()->create();
    $this->actingAs($scanner);

    $payload = [
        'device_id' => 'device-003',
        'device_name' => 'Tablet Scanner',
    ];

    $this->postJson(route('scanner.heartbeat'), $payload)->assertOk();
    $this->postJson(route('scanner.heartbeat'), $payload)->assertOk();

    expect(ScannerSession::where('user_id', $scanner->id)->where('device_id', 'device-003')->count())->toBe(1);
});

test('admin dapat membuka monitoring', function () {
    $scanner = User::factory()->scanner()->create(['name' => 'Andi Scanner']);

    ScannerSession::create([
        'user_id' => $scanner->id,
        'device_id' => 'device-004',
        'device_name' => 'Laptop Scanner',
        'last_seen_at' => now(),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.scanners.monitoring'))
        ->assertOk()
        ->assertSee('Monitoring Scanner')
        ->assertSee('Andi Scanner')
        ->assertSee('Laptop Scanner');
});

test('scanner tidak dapat membuka monitoring', function () {
    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('admin.scanners.monitoring'))
        ->assertForbidden();
});

test('offline calculation benar', function () {
    $onlineScanner = User::factory()->scanner()->create(['name' => 'Online Scanner']);
    $offlineScanner = User::factory()->scanner()->create(['name' => 'Offline Scanner']);

    $online = ScannerSession::create([
        'user_id' => $onlineScanner->id,
        'device_id' => 'online-device',
        'device_name' => 'Online Device',
        'last_seen_at' => now()->subMinute(),
    ]);

    $offline = ScannerSession::create([
        'user_id' => $offlineScanner->id,
        'device_id' => 'offline-device',
        'device_name' => 'Offline Device',
        'last_seen_at' => now()->subMinutes(3),
    ]);

    expect($online->isOnline())->toBeTrue()
        ->and($offline->isOnline())->toBeFalse();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.scanners.monitoring'))
        ->assertOk()
        ->assertSee('scanner-session-status-'.$online->id, false)
        ->assertSee('Online')
        ->assertSee('scanner-session-status-'.$offline->id, false)
        ->assertSee('Offline');
});

test('success check-in update last scan at', function () {
    $scanner = User::factory()->scanner()->create();
    $ticket = monitoringTicket('MON-777');
    ScannerEventAssignment::create([
        'user_id' => $scanner->id,
        'event_id' => $ticket->event_id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($scanner)
        ->postJson(route('scanner.heartbeat'), [
            'device_id' => 'device-777',
            'device_name' => 'Phone Scanner',
        ])
        ->assertOk();

    $session = ScannerSession::where('user_id', $scanner->id)->where('device_id', 'device-777')->firstOrFail();

    expect($session->last_scan_at)->toBeNull();

    $this->actingAs($scanner)
        ->postJson(route('scanner.check-in'), [
            'code' => $ticket->qr_code,
            'device_id' => 'device-777',
            'device_name' => 'Phone Scanner',
        ])
        ->assertOk()
        ->assertJsonPath('status', TicketCheckInService::SUCCESS);

    expect($session->fresh()->last_scan_at)->not->toBeNull();
});
