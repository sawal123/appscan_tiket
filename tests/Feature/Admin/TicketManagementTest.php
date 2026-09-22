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
