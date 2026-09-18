<?php

use App\Enums\UserRole;
use App\Livewire\Admin\ScannerCreate;
use App\Livewire\Admin\Scanners;
use App\Models\CheckInLog;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function scannerUser(array $attributes = []): User
{
    return User::factory()->scanner()->create($attributes);
}

function scannerCheckInLog(User $scanner): CheckInLog
{
    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create();
    $ticket = Ticket::factory()->for($category, 'ticketCategory')->create([
        'event_id' => $event->id,
        'checked_in_at' => now(),
    ]);

    return CheckInLog::create([
        'ticket_id' => $ticket->id,
        'scanner_id' => $scanner->id,
        'qr_code' => $ticket->qr_code,
        'status' => CheckInLog::STATUS_SUCCESS,
        'scanned_at' => now(),
    ]);
}

test('guest tidak dapat membuka scanner management', function () {
    $this->get(route('admin.scanners'))->assertRedirect(route('login'));
    $this->get(route('admin.scanners.create'))->assertRedirect(route('login'));
});

test('scanner tidak dapat membuka admin scanner management', function () {
    $this->actingAs(scannerUser())
        ->get(route('admin.scanners'))
        ->assertForbidden();

    $this->actingAs(scannerUser())
        ->get(route('admin.scanners.create'))
        ->assertForbidden();
});

test('admin dapat melihat daftar scanner', function () {
    $this->actingAs(User::factory()->admin()->create());

    scannerUser(['name' => 'Andi', 'email' => 'andi.scan@gateflow.test']);
    User::factory()->admin()->create(['name' => 'Admin Lain']);

    $this->get(route('admin.scanners'))
        ->assertOk()
        ->assertSee('Andi')
        ->assertSee('andi.scan@gateflow.test')
        ->assertSee('Aktif')
        ->assertDontSee('Admin Lain');
});

test('admin dapat membuat scanner', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ScannerCreate::class)
        ->set('name', 'Andi')
        ->set('email', 'andi.scan@gateflow.test')
        ->set('password', 'rahasia123')
        ->set('password_confirmation', 'rahasia123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.scanners'));

    expect(User::where('email', 'andi.scan@gateflow.test')->first())
        ->not->toBeNull()
        ->name->toBe('Andi');
});

test('user baru otomatis memiliki role scanner', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ScannerCreate::class)
        ->set('name', 'Petugas')
        ->set('email', 'petugas@gateflow.test')
        ->set('password', 'rahasia123')
        ->set('password_confirmation', 'rahasia123')
        ->call('save')
        ->assertHasNoErrors();

    $scanner = User::where('email', 'petugas@gateflow.test')->sole();

    expect($scanner->role)->toBe(UserRole::Scanner)
        ->and($scanner->isAdmin())->toBeFalse()
        ->and($scanner->is_active)->toBeTrue();
});

test('password tersimpan hashed', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ScannerCreate::class)
        ->set('name', 'Petugas')
        ->set('email', 'hashed@gateflow.test')
        ->set('password', 'rahasia123')
        ->set('password_confirmation', 'rahasia123')
        ->call('save')
        ->assertHasNoErrors();

    $scanner = User::where('email', 'hashed@gateflow.test')->sole();

    expect($scanner->password)->not->toBe('rahasia123')
        ->and(Hash::check('rahasia123', $scanner->password))->toBeTrue();
});

test('jumlah scan scanner benar dari check in logs', function () {
    $this->actingAs(User::factory()->admin()->create());

    $scanner = scannerUser(['name' => 'Andi']);
    $other = scannerUser(['name' => 'Budi']);

    scannerCheckInLog($scanner);
    scannerCheckInLog($scanner);
    scannerCheckInLog($scanner);
    scannerCheckInLog($other);

    $this->get(route('admin.scanners'))
        ->assertOk()
        ->assertSee('3 scan')
        ->assertSee('1 scan');
});

test('edit scanner tidak mengubah role', function () {
    $this->actingAs(User::factory()->admin()->create());

    $scanner = scannerUser(['name' => 'Nama Lama']);

    Livewire::test(Scanners::class)
        ->call('edit', $scanner->id)
        ->set('name', 'Nama Baru')
        ->set('email', 'baru@gateflow.test')
        ->call('save')
        ->assertHasNoErrors();

    expect($scanner->fresh())
        ->name->toBe('Nama Baru')
        ->email->toBe('baru@gateflow.test')
        ->role->toBe(UserRole::Scanner);
});

test('admin dapat menonaktifkan scanner', function () {
    $this->actingAs(User::factory()->admin()->create());

    $scanner = scannerUser();

    Livewire::test(Scanners::class)
        ->call('toggleActive', $scanner->id)
        ->assertHasNoErrors();

    expect($scanner->fresh()->is_active)->toBeFalse();
});

test('scanner dapat login', function () {
    scannerUser(['email' => 'andi.scan@gateflow.test']);

    $this->post(route('login.store'), [
        'email' => 'andi.scan@gateflow.test',
        'password' => 'password',
    ])->assertRedirect('/scanner');

    $this->assertAuthenticated();
});

test('scanner tetap tidak bisa akses admin', function () {
    $this->actingAs(scannerUser())
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs(scannerUser())
        ->get(route('admin.scanners'))
        ->assertForbidden();
});
