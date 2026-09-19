<?php

use App\Models\Event;
use App\Models\ScannerEventAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function assignOperationalScanner(User $scanner, Event $event): void
{
    ScannerEventAssignment::create([
        'user_id' => $scanner->id,
        'event_id' => $event->id,
        'assigned_at' => now(),
    ]);
}

test('scanner aktif dapat mengakses halaman scanner', function () {
    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('scanner.index'))
        ->assertOk()
        ->assertSee('TICKET SCANNER');
});

test('scanner melihat identitas sendiri di header', function () {
    $scanner = User::factory()->scanner()->create(['name' => 'Andi Pratama']);

    $this->actingAs($scanner)
        ->get(route('scanner.index'))
        ->assertOk()
        ->assertSee('<strong data-testid="scanner-name" title="Andi Pratama">Andi Pratama</strong>', false)
        ->assertSee('<small data-testid="scanner-role">Scanner</small>', false);
});

test('admin yang membuka scanner melihat role sendiri', function () {
    $this->actingAs(User::factory()->admin()->create(['name' => 'Admin Gateflow']))
        ->get(route('scanner.index'))
        ->assertOk()
        ->assertSee('<small data-testid="scanner-role">Admin</small>', false);
});

test('tombol logout tersedia di header scanner', function () {
    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('scanner.index'))
        ->assertOk()
        ->assertSee('scanner-logout-button', false);
});

test('event aktif tampil di header scanner', function () {
    $event = Event::factory()->active()->create(['name' => 'Festival ABC 2026']);
    $scanner = User::factory()->scanner()->create();
    assignOperationalScanner($scanner, $event);

    $this->actingAs($scanner)
        ->get(route('scanner.index'))
        ->assertOk()
        ->assertSee('Festival ABC 2026');
});

test('scanner tanpa event assignment tampil warning dan pemindaian dinonaktifkan', function () {
    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('scanner.index'))
        ->assertOk()
        ->assertSee('no-active-event', false)
        ->assertSee('Scanner belum memiliki event')
        ->assertSee('Belum ada event assigned')
        ->assertDontSee('camera-mode-panel', false)
        ->assertDontSee('activate-camera-button', false)
        ->assertDontSee('hardware-scanner-input', false)
        ->assertDontSee('manual-input-open-button', false);
});

test('scan tetap tersedia saat scanner memiliki event assignment', function () {
    $event = Event::factory()->active()->create();
    $scanner = User::factory()->scanner()->create();
    assignOperationalScanner($scanner, $event);

    $this->actingAs($scanner)
        ->get(route('scanner.index'))
        ->assertOk()
        ->assertSee('camera-mode-panel', false)
        ->assertSee('hardware-scanner-input', false)
        ->assertDontSee('no-active-event', false);
});

test('logout bekerja', function () {
    $scanner = User::factory()->scanner()->create();

    $this->actingAs($scanner)
        ->post(route('logout'))
        ->assertRedirect();

    $this->assertGuest();
});

test('scanner nonaktif ditolak', function () {
    $this->actingAs(User::factory()->scanner()->create(['is_active' => false]))
        ->get(route('scanner.index'))
        ->assertForbidden();
});
