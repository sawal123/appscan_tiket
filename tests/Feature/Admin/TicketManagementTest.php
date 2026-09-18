<?php

use App\Livewire\Admin\TicketCreate;
use App\Livewire\Admin\Tickets;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('guest tidak akses tickets', function () {
    $this->get(route('admin.tickets'))->assertRedirect(route('login'));
});

test('scanner mendapat 403 dari tickets', function () {
    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('admin.tickets'))
        ->assertForbidden();
});

test('admin bisa akses tickets', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.tickets'))
        ->assertOk()
        ->assertSee('Daftar Tiket');
});

test('admin bisa create ticket', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);

    Livewire::test(TicketCreate::class)
        ->set('event_id', $event->id)
        ->set('ticket_category_id', $category->id)
        ->set('qr_code', 'abc001')
        ->call('save')
        ->assertHasNoErrors();

    expect(Ticket::where('qr_code', 'ABC001')->first())
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
        'code' => 'ABC001',
        'qr_code' => 'ABC001',
    ]);

    Livewire::test(TicketCreate::class)
        ->set('event_id', $event->id)
        ->set('ticket_category_id', $category->id)
        ->set('qr_code', 'ABC001')
        ->call('save')
        ->assertHasErrors(['qr_code' => 'unique']);

    expect(Ticket::where('qr_code', 'ABC001')->count())->toBe(1);
});

test('CSV valid berhasil import', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $event = Event::factory()->create();
    TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    TicketCategory::factory()->for($event)->create(['name' => 'Regular']);

    Livewire::test(Tickets::class)
        ->set('import_event_id', $event->id)
        ->set('importFile', csvUpload("qr_code,ticket_category\nABC001,VIP\nABC002,Regular\n"))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('importResult.success', 2)
        ->assertSet('importResult.failed', 0);

    expect(Ticket::where('event_id', $event->id)->count())->toBe(2)
        ->and(Ticket::where('qr_code', 'ABC001')->where('registered_by', $admin->id)->exists())->toBeTrue()
        ->and(Ticket::where('qr_code', 'ABC002')->exists())->toBeTrue();
});

test('CSV duplicate gagal', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    Ticket::factory()->for($category, 'ticketCategory')->create([
        'event_id' => $event->id,
        'code' => 'ABC001',
        'qr_code' => 'ABC001',
    ]);

    Livewire::test(Tickets::class)
        ->set('import_event_id', $event->id)
        ->set('importFile', csvUpload("qr_code,ticket_category\nABC001,VIP\n"))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('importResult.success', 0)
        ->assertSet('importResult.failed', 1);

    expect(Ticket::where('qr_code', 'ABC001')->count())->toBe(1);
});

test('category invalid gagal', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    TicketCategory::factory()->for($event)->create(['name' => 'VIP']);

    Livewire::test(Tickets::class)
        ->set('import_event_id', $event->id)
        ->set('importFile', csvUpload("qr_code,ticket_category\nABC999,Regular\n"))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('importResult.success', 0)
        ->assertSet('importResult.failed', 1);

    expect(Ticket::where('qr_code', 'ABC999')->exists())->toBeFalse();
});

function csvUpload(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('tickets.csv', $content);
}
