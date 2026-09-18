<?php

use App\Livewire\Admin\TicketCreate;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('admin dapat membuka create ticket', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.tickets.create'))
        ->assertOk()
        ->assertSee('Registrasi QR')
        ->assertSee('Camera QR')
        ->assertSee('Hardware Scanner 2D');
});

test('camera memakai qrCameraScanner dan bukan BarcodeDetector', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.tickets.create'))
        ->assertOk()
        ->assertSee('qrCameraScanner')
        ->assertSee('camera-status')
        ->assertDontSee('BarcodeDetector', false);
});

test('hardware scanner tetap memakai input keyboard dan enter', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.tickets.create'))
        ->assertOk()
        ->assertSee('hardware-scanner-input', false)
        ->assertSee('submitHardwareCode')
        ->assertSee('Input otomatis diproses setelah scanner mengirim Enter');
});

test('tombol scan ulang memakai reset tanpa restart kamera', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.tickets.create'))
        ->assertOk()
        ->assertSee('resetScan')
        ->assertSee('qr-camera:reset', false)
        ->assertSee('qr-camera:rearm', false)
        ->assertSee('activate-camera-button', false);
});

test('scanner tidak dapat akses', function () {
    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('admin.tickets.create'))
        ->assertForbidden();
});

test('create ticket melalui QR berhasil', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $event = Event::factory()->active()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);

    Livewire::test(TicketCreate::class)
        ->set('event_id', $event->id)
        ->set('ticket_category_id', $category->id)
        ->call('register', 'qr-scan-001')
        ->assertHasNoErrors()
        ->assertSet('qr_code', '')
        ->assertSee('QR-SCAN-001')
        ->assertSee('berhasil didaftarkan');

    expect(Ticket::where('qr_code', 'QR-SCAN-001')->first())
        ->not->toBeNull()
        ->event_id->toBe($event->id)
        ->ticket_category_id->toBe($category->id)
        ->status->toBe(Ticket::STATUS_REGISTERED)
        ->registered_by->toBe($admin->id);
});

test('duplicate QR ditolak', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);

    Ticket::factory()->for($category, 'ticketCategory')->create([
        'event_id' => $event->id,
        'code' => 'DUP-001',
        'qr_code' => 'DUP-001',
    ]);

    Livewire::test(TicketCreate::class)
        ->set('event_id', $event->id)
        ->set('ticket_category_id', $category->id)
        ->set('qr_code', 'dup-001')
        ->call('save')
        ->assertHasErrors(['qr_code' => 'unique'])
        ->assertSee('QR sudah terdaftar');

    expect(Ticket::where('qr_code', 'DUP-001')->count())->toBe(1);
});

test('event wajib', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(TicketCreate::class)
        ->set('qr_code', 'NO-EVENT-001')
        ->call('save')
        ->assertHasErrors(['event_id' => 'required']);

    expect(Ticket::where('qr_code', 'NO-EVENT-001')->exists())->toBeFalse();
});

test('category wajib', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();

    Livewire::test(TicketCreate::class)
        ->set('event_id', $event->id)
        ->set('qr_code', 'NO-CATEGORY-001')
        ->call('save')
        ->assertHasErrors(['ticket_category_id' => 'required']);

    expect(Ticket::where('qr_code', 'NO-CATEGORY-001')->exists())->toBeFalse();
});

test('sidebar tidak menampilkan menu ticket duplikat', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Registrasi QR')
        ->assertDontSee('nav-tickets-link', false);
});
