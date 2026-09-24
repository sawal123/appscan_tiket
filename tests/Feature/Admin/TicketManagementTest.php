<?php

use App\Livewire\Admin\TicketCreate;
use App\Livewire\Admin\Tickets;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

uses(TestCase::class, RefreshDatabase::class);

test('ticket list paginates instead of rendering all rows', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create();

    $baseCreatedAt = now()->subMinutes(120);

    for ($i = 1; $i <= 120; $i++) {
        managedTicket($event, $category, 'PAGE'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), [
            'created_at' => $baseCreatedAt->copy()->addMinutes($i),
            'updated_at' => $baseCreatedAt->copy()->addMinutes($i),
        ]);
    }

    Livewire::test(Tickets::class)
        ->assertSee('120 tiket total')
        ->assertSee('Halaman 1 dari 3')
        ->assertSee('PAGE0120')
        ->assertDontSee('PAGE0001')
        ->call('gotoPage', 3)
        ->assertSee('PAGE0001')
        ->assertDontSee('PAGE0120');
});

test('ticket search resets pagination and only renders matching page', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create();

    for ($i = 1; $i <= 60; $i++) {
        managedTicket($event, $category, 'SEARCH'.str_pad((string) $i, 4, '0', STR_PAD_LEFT));
    }

    Livewire::test(Tickets::class)
        ->call('gotoPage', 2)
        ->set('search', 'SEARCH0001')
        ->assertSee('SEARCH0001')
        ->assertSee('Halaman 1 dari 1')
        ->assertDontSee('SEARCH0060');
});

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

test('admin dapat melihat tombol edit untuk tiket belum check-in', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $ticket = managedTicket($event, $category, 'EDIT001');
    $checkedInTicket = managedTicket($event, $category, 'EDIT002', ['checked_in_at' => now()]);

    $this->get(route('admin.tickets'))
        ->assertOk()
        ->assertSee('data-testid="ticket-edit-'.$ticket->id.'"', false)
        ->assertDontSee('data-testid="ticket-edit-'.$checkedInTicket->id.'"', false);
});

test('admin dapat mengubah QR tiket belum check-in', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $ticket = managedTicket($event, $category, 'EDITQR001');

    Livewire::test(Tickets::class)
        ->call('edit', $ticket->id)
        ->set('editingQrCode', 'editqr002')
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect($ticket->fresh())
        ->qr_code->toBe('EDITQR002')
        ->code->toBe('EDITQR002')
        ->event_id->toBe($event->id)
        ->registered_by->toBe($ticket->registered_by);
});

test('admin dapat mengubah kategori tiket belum check-in', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $firstCategory = TicketCategory::factory()->for($event)->create(['name' => 'Regular']);
    $secondCategory = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $ticket = managedTicket($event, $firstCategory, 'EDITCAT001');

    Livewire::test(Tickets::class)
        ->call('edit', $ticket->id)
        ->set('editingCategoryId', $secondCategory->id)
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect($ticket->fresh())
        ->ticket_category_id->toBe($secondCategory->id)
        ->event_id->toBe($event->id);
});

test('edit QR duplicate ditolak', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $ticket = managedTicket($event, $category, 'EDITDUP001');
    managedTicket($event, $category, 'EDITDUP002');

    Livewire::test(Tickets::class)
        ->call('edit', $ticket->id)
        ->set('editingQrCode', 'EDITDUP002')
        ->call('saveEdit')
        ->assertHasErrors(['editingQrCode']);

    expect($ticket->fresh()->qr_code)->toBe('EDITDUP001');
});

test('edit kategori event lain ditolak', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $otherEvent = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $otherCategory = TicketCategory::factory()->for($otherEvent)->create(['name' => 'Regular']);
    $ticket = managedTicket($event, $category, 'EDITOTHER001');

    Livewire::test(Tickets::class)
        ->call('edit', $ticket->id)
        ->set('editingCategoryId', $otherCategory->id)
        ->call('saveEdit')
        ->assertHasErrors(['editingCategoryId']);

    expect($ticket->fresh())
        ->ticket_category_id->toBe($category->id)
        ->event_id->toBe($event->id);
});

test('tiket checked-in tidak dapat diedit dan attempt tidak mengubah database', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $ticket = managedTicket($event, $category, 'LOCKEDIT001', [
        'checked_in_at' => now(),
        'status' => Ticket::STATUS_CHECKED_IN,
    ]);

    Livewire::test(Tickets::class)
        ->call('edit', $ticket->id)
        ->set('editingTicketId', $ticket->id)
        ->set('editingQrCode', 'LOCKEDIT002')
        ->set('editingCategoryId', $category->id)
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect($ticket->fresh())
        ->qr_code->toBe('LOCKEDIT001')
        ->checked_in_at->not->toBeNull()
        ->status->toBe(Ticket::STATUS_CHECKED_IN);
});

test('admin dapat menghapus tiket belum check-in', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $ticket = managedTicket($event, $category, 'DELETE001');

    Livewire::test(Tickets::class)
        ->call('deleteTicket', $ticket->id)
        ->assertHasNoErrors();

    expect(Ticket::whereKey($ticket->id)->exists())->toBeFalse();
});

test('tiket checked-in tidak dapat dihapus dan attempt tidak menghapus database', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $ticket = managedTicket($event, $category, 'LOCKDELETE001', ['checked_in_at' => now()]);

    Livewire::test(Tickets::class)
        ->call('deleteTicket', $ticket->id)
        ->assertHasNoErrors();

    expect(Ticket::whereKey($ticket->id)->exists())->toBeTrue();
});

test('admin dapat memilih beberapa tiket belum check-in dan bulk delete menghapus tiket yang dipilih', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $first = managedTicket($event, $category, 'BULKDEL001');
    $second = managedTicket($event, $category, 'BULKDEL002');

    Livewire::test(Tickets::class)
        ->set('selectedTicketIds', [$first->id, $second->id])
        ->assertSet('selectedTicketIds', [$first->id, $second->id])
        ->call('bulkDelete')
        ->assertSet('selectedTicketIds', [])
        ->assertHasNoErrors();

    expect(Ticket::whereIn('id', [$first->id, $second->id])->exists())->toBeFalse();
});

test('tombol delete tiket membuka modal konfirmasi tanpa menghapus', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $ticket = managedTicket($event, $category, 'MODAL001');

    Livewire::test(Tickets::class)
        ->call('confirmDeleteTicket', $ticket->id)
        ->assertSet('showDeleteModal', true)
        ->assertSet('deletingTicketId', $ticket->id)
        ->assertSet('deletingTicketLabel', 'MODAL001');

    expect(Ticket::whereKey($ticket->id)->exists())->toBeTrue();
});

test('modal konfirmasi delete tiket menampilkan QR dan teks konfirmasi', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $ticket = managedTicket($event, $category, 'MODAL002');

    Livewire::test(Tickets::class)
        ->call('confirmDeleteTicket', $ticket->id)
        ->assertSet('showDeleteModal', true)
        ->assertSee('Hapus Tiket?')
        ->assertSee('MODAL002')
        ->assertSee('Data yang sudah dihapus tidak dapat dikembalikan.');
});

test('batal pada modal delete tiket menutup modal dan tiket tetap ada', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $ticket = managedTicket($event, $category, 'MODAL003');

    Livewire::test(Tickets::class)
        ->call('confirmDeleteTicket', $ticket->id)
        ->call('cancelDeleteTicket')
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingTicketId', null)
        ->assertSet('deletingTicketLabel', '');

    expect(Ticket::whereKey($ticket->id)->exists())->toBeTrue();
});

test('konfirmasi modal delete tiket menjalankan delete dan menutup modal', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $ticket = managedTicket($event, $category, 'MODAL004');

    Livewire::test(Tickets::class)
        ->call('confirmDeleteTicket', $ticket->id)
        ->call('deleteTicket', $ticket->id)
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingTicketId', null)
        ->assertHasNoErrors();

    expect(Ticket::whereKey($ticket->id)->exists())->toBeFalse();
});

test('confirm delete tiket checked-in tidak membuka modal', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $ticket = managedTicket($event, $category, 'MODAL005', ['checked_in_at' => now()]);

    Livewire::test(Tickets::class)
        ->call('confirmDeleteTicket', $ticket->id)
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingTicketId', null);

    expect(Ticket::whereKey($ticket->id)->exists())->toBeTrue();
});

test('tombol bulk delete membuka modal konfirmasi tanpa menghapus', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $first = managedTicket($event, $category, 'BULKMODAL001');
    $second = managedTicket($event, $category, 'BULKMODAL002');

    Livewire::test(Tickets::class)
        ->set('selectedTicketIds', [$first->id, $second->id])
        ->call('confirmBulkDelete')
        ->assertSet('showBulkDeleteModal', true);

    expect(Ticket::whereIn('id', [$first->id, $second->id])->exists())->toBeTrue();
});

test('batal pada modal bulk delete menutup modal dan tiket tetap ada', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $first = managedTicket($event, $category, 'BULKMODAL003');
    $second = managedTicket($event, $category, 'BULKMODAL004');

    Livewire::test(Tickets::class)
        ->set('selectedTicketIds', [$first->id, $second->id])
        ->call('confirmBulkDelete')
        ->call('cancelBulkDelete')
        ->assertSet('showBulkDeleteModal', false);

    expect(Ticket::whereIn('id', [$first->id, $second->id])->exists())->toBeTrue();
});

test('konfirmasi modal bulk delete menjalankan bulk delete dan menutup modal', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $first = managedTicket($event, $category, 'BULKMODAL005');
    $second = managedTicket($event, $category, 'BULKMODAL006');

    Livewire::test(Tickets::class)
        ->set('selectedTicketIds', [$first->id, $second->id])
        ->call('confirmBulkDelete')
        ->call('bulkDelete')
        ->assertSet('showBulkDeleteModal', false)
        ->assertSet('selectedTicketIds', [])
        ->assertHasNoErrors();

    expect(Ticket::whereIn('id', [$first->id, $second->id])->exists())->toBeFalse();
});

test('checked-in ticket tidak dapat ikut dipilih', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $checkedInTicket = managedTicket($event, $category, 'SELECTLOCK001', ['checked_in_at' => now()]);

    Livewire::test(Tickets::class)
        ->set('selectedTicketIds', [$checkedInTicket->id])
        ->assertSet('selectedTicketIds', []);
});

test('manual selected ID checked-in tidak menyebabkan tiket tersebut terhapus', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $valid = managedTicket($event, $category, 'TAMPER001');
    $checkedInTicket = managedTicket($event, $category, 'TAMPER002', ['checked_in_at' => now()]);

    Livewire::test(Tickets::class)
        ->set('selectedTicketIds', [$valid->id])
        ->set('selectedTicketIds', [$valid->id, $checkedInTicket->id])
        ->call('bulkDelete')
        ->assertHasNoErrors();

    expect(Ticket::whereKey($valid->id)->exists())->toBeFalse()
        ->and(Ticket::whereKey($checkedInTicket->id)->exists())->toBeTrue();
});

test('bulk delete hanya menghapus tiket dengan checked_in_at null', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $valid = managedTicket($event, $category, 'BULKNULL001');
    $checkedInTicket = managedTicket($event, $category, 'BULKNULL002', ['checked_in_at' => now()]);

    Livewire::test(Tickets::class)
        ->set('selectedTicketIds', [$valid->id, $checkedInTicket->id])
        ->call('bulkDelete')
        ->assertSet('selectedTicketIds', []);

    expect(Ticket::whereKey($valid->id)->exists())->toBeFalse()
        ->and(Ticket::whereKey($checkedInTicket->id)->exists())->toBeTrue();
});

test('selection tidak menyebabkan tiket dari filter sebelumnya ikut terhapus', function () {
    $this->actingAs(User::factory()->admin()->create());

    $eventA = Event::factory()->create(['name' => 'Event A']);
    $eventB = Event::factory()->create(['name' => 'Event B']);
    $categoryA = TicketCategory::factory()->for($eventA)->create(['name' => 'VIP']);
    $categoryB = TicketCategory::factory()->for($eventB)->create(['name' => 'VIP']);
    $ticketA = managedTicket($eventA, $categoryA, 'FILTER001');
    $ticketB = managedTicket($eventB, $categoryB, 'FILTER002');

    Livewire::test(Tickets::class)
        ->set('eventFilter', $eventA->id)
        ->set('selectedTicketIds', [$ticketA->id])
        ->set('eventFilter', $eventB->id)
        ->assertSet('selectedTicketIds', [])
        ->set('selectAllDisplayed', true)
        ->call('bulkDelete');

    expect(Ticket::whereKey($ticketA->id)->exists())->toBeTrue()
        ->and(Ticket::whereKey($ticketB->id)->exists())->toBeFalse();
});

test('select all hanya memilih tiket eligible', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    $valid = managedTicket($event, $category, 'SELECTALL001');
    $checkedInTicket = managedTicket($event, $category, 'SELECTALL002', ['checked_in_at' => now()]);

    $component = Livewire::test(Tickets::class)
        ->set('selectAllDisplayed', true);

    expect($component->get('selectedTicketIds'))->toEqualCanonicalizing([$valid->id])
        ->and($component->get('selectedTicketIds'))->not->toContain($checkedInTicket->id);
});

test('download template excel dapat diakses admin dan berisi header import', function () {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get(route('admin.tickets.import-template'));

    $response->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    expect($response->headers->get('content-disposition'))->toContain('ticket-import-template.xlsx');

    $headers = xlsxFirstRow((string) $response->getContent());

    expect($headers)->toBe(['qr_code', 'ticket_category']);
});

test('guest dan scanner tidak dapat mengakses endpoint template admin', function () {
    $this->get(route('admin.tickets.import-template'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('admin.tickets.import-template'))
        ->assertForbidden();
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

test('XLSX valid berhasil import', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $event = Event::factory()->create();
    TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    TicketCategory::factory()->for($event)->create(['name' => 'Regular']);

    Livewire::test(Tickets::class)
        ->set('import_event_id', $event->id)
        ->set('importFile', xlsxUpload([
            ['qr_code', 'ticket_category'],
            ['XLSX001', 'VIP'],
            ['XLSX002', 'Regular'],
        ]))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('importResult.success', 2)
        ->assertSet('importResult.failed', 0);

    expect(Ticket::where('event_id', $event->id)->count())->toBe(2)
        ->and(Ticket::where('qr_code', 'XLSX001')->where('registered_by', $admin->id)->exists())->toBeTrue()
        ->and(Ticket::where('qr_code', 'XLSX002')->exists())->toBeTrue();
});

test('file format tidak didukung ditolak', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();

    Livewire::test(Tickets::class)
        ->set('import_event_id', $event->id)
        ->set('importFile', UploadedFile::fake()->create('tickets.pdf', 1, 'application/pdf'))
        ->call('import')
        ->assertHasErrors(['importFile']);
});

test('header CSV invalid ditolak', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();

    Livewire::test(Tickets::class)
        ->set('import_event_id', $event->id)
        ->set('importFile', csvUpload("qr,ticket_category\nABC001,VIP\n"))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('importResult.success', 0)
        ->assertSet('importResult.failed', 1);

    expect(Ticket::count())->toBe(0);
});

test('header XLSX invalid ditolak', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();

    Livewire::test(Tickets::class)
        ->set('import_event_id', $event->id)
        ->set('importFile', xlsxUpload([
            ['qr', 'ticket_category'],
            ['ABC001', 'VIP'],
        ]))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('importResult.success', 0)
        ->assertSet('importResult.failed', 1);

    expect(Ticket::count())->toBe(0);
});

test('QR kosong gagal saat import', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    TicketCategory::factory()->for($event)->create(['name' => 'VIP']);

    Livewire::test(Tickets::class)
        ->set('import_event_id', $event->id)
        ->set('importFile', csvUpload("qr_code,ticket_category\n,VIP\n"))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('importResult.success', 0)
        ->assertSet('importResult.failed', 1);

    expect(Ticket::count())->toBe(0);
});

test('duplicate QR dalam file gagal', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    TicketCategory::factory()->for($event)->create(['name' => 'VIP']);

    Livewire::test(Tickets::class)
        ->set('import_event_id', $event->id)
        ->set('importFile', csvUpload("qr_code,ticket_category\nDUP001,VIP\ndup001,VIP\n"))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('importResult.success', 1)
        ->assertSet('importResult.failed', 1);

    expect(Ticket::where('qr_code', 'DUP001')->count())->toBe(1);
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

test('kategori dari event lain gagal', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $otherEvent = Event::factory()->create();
    TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    TicketCategory::factory()->for($otherEvent)->create(['name' => 'Regular']);

    Livewire::test(Tickets::class)
        ->set('import_event_id', $event->id)
        ->set('importFile', csvUpload("qr_code,ticket_category\nABC999,Regular\n"))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('importResult.success', 0)
        ->assertSet('importResult.failed', 1);

    expect(Ticket::where('qr_code', 'ABC999')->exists())->toBeFalse();
});

test('event yang dipilih digunakan untuk semua ticket valid', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $otherEvent = Event::factory()->create();
    TicketCategory::factory()->for($event)->create(['name' => 'VIP']);
    TicketCategory::factory()->for($otherEvent)->create(['name' => 'VIP']);

    Livewire::test(Tickets::class)
        ->set('import_event_id', $event->id)
        ->set('importFile', csvUpload("qr_code,ticket_category\nEVENT001,VIP\nEVENT002,VIP\n"))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('importResult.success', 2)
        ->assertSet('importResult.failed', 0);

    expect(Ticket::where('event_id', $event->id)->count())->toBe(2)
        ->and(Ticket::where('event_id', $otherEvent->id)->count())->toBe(0);
});

test('registered_by menggunakan admin yang melakukan import', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $event = Event::factory()->create();
    TicketCategory::factory()->for($event)->create(['name' => 'VIP']);

    Livewire::test(Tickets::class)
        ->set('import_event_id', $event->id)
        ->set('importFile', csvUpload("qr_code,ticket_category\nADMIN001,VIP\n"))
        ->call('import')
        ->assertHasNoErrors();

    expect(Ticket::where('qr_code', 'ADMIN001')->sole()->registered_by)->toBe($admin->id);
});

test('campuran valid dan invalid menghasilkan success dan failed sesuai baris', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    TicketCategory::factory()->for($event)->create(['name' => 'VIP']);

    Livewire::test(Tickets::class)
        ->set('import_event_id', $event->id)
        ->set('importFile', csvUpload("qr_code,ticket_category\nMIX001,VIP\n,VIP\nMIX002,Regular\nMIX003,VIP\n"))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('importResult.success', 2)
        ->assertSet('importResult.failed', 2);

    expect(Ticket::whereIn('qr_code', ['MIX001', 'MIX003'])->count())->toBe(2)
        ->and(Ticket::where('qr_code', 'MIX002')->exists())->toBeFalse();
});

test('import ribuan QR tetap dalam query budget', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();
    TicketCategory::factory()->for($event)->create(['name' => 'VIP']);

    $rows = ['qr_code,ticket_category'];

    for ($index = 1; $index <= 1200; $index++) {
        $rows[] = 'BULK-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT).',VIP';
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $result = app(TicketImportService::class)->import($event->id, csvUpload(implode("\n", $rows)."\n"), $admin);
    $queryCount = count(DB::getQueryLog());

    DB::disableQueryLog();

    expect($result['success'])->toBe(1200)
        ->and($result['failed'])->toBe(0)
        ->and(Ticket::where('event_id', $event->id)->count())->toBe(1200)
        ->and($queryCount)->toBeLessThanOrEqual(8);
});

function csvUpload(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('tickets.csv', $content);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function managedTicket(Event $event, TicketCategory $category, string $qrCode, array $attributes = []): Ticket
{
    return Ticket::factory()->for($category, 'ticketCategory')->create([
        'event_id' => $event->id,
        'ticket_category_id' => $category->id,
        'code' => $qrCode,
        'qr_code' => $qrCode,
        ...$attributes,
    ]);
}

/**
 * @return array<int, string>
 */
function xlsxFirstRow(string $content): array
{
    $path = tempnam(sys_get_temp_dir(), 'template').'.xlsx';
    file_put_contents($path, $content);

    $zip = new ZipArchive;
    $zip->open($path);
    $sheetXml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    @unlink($path);

    $sheet = simplexml_load_string($sheetXml);
    $values = [];

    foreach ($sheet->sheetData->row[0]->c as $cell) {
        $values[] = (string) $cell->is->t;
    }

    return $values;
}

/**
 * @param  array<int, array<int, string>>  $rows
 */
function xlsxUpload(array $rows): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'tickets').'.xlsx';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
    $zip->addFromString('xl/worksheets/sheet1.xml', worksheetXml($rows));
    $zip->close();

    $content = (string) file_get_contents($path);
    @unlink($path);

    return UploadedFile::fake()->createWithContent('tickets.xlsx', $content);
}

/**
 * @param  array<int, array<int, string>>  $rows
 */
function worksheetXml(array $rows): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

    foreach ($rows as $rowIndex => $row) {
        $rowNumber = $rowIndex + 1;
        $xml .= '<row r="'.$rowNumber.'">';

        foreach ($row as $columnIndex => $value) {
            $cell = chr(65 + $columnIndex).$rowNumber;
            $xml .= '<c r="'.$cell.'" t="inlineStr"><is><t>'.htmlspecialchars($value, ENT_XML1).'</t></is></c>';
        }

        $xml .= '</row>';
    }

    return $xml.'</sheetData></worksheet>';
}
