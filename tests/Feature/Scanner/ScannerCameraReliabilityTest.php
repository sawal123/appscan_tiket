<?php

use App\Models\Event;
use App\Models\ScannerEventAssignment;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketCheckInService;
use App\Services\TicketValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function cameraReliabilitySource(string $relativePath): string
{
    $path = base_path($relativePath);

    expect(file_exists($path))->toBeTrue();

    return (string) file_get_contents($path);
}

test('scanner tetap memakai ZXing', function () {
    $scanner = cameraReliabilitySource('resources/js/scanner.js');
    $component = cameraReliabilitySource('resources/js/components/qr-camera-scanner.js');

    expect($scanner)
        ->toContain('createQrCodeReader')
        ->toContain('./components/qr-camera-scanner')
        ->toContain('decodeFromVideoElement');

    expect($component)
        ->toContain('@zxing/browser')
        ->toContain('@zxing/library')
        ->toContain('BrowserQRCodeReader')
        ->toContain('DecodeHintType.TRY_HARDER')
        ->toContain('DecodeHintType.POSSIBLE_FORMATS')
        ->toContain('BarcodeFormat.QR_CODE');
});

test('scanner tidak menggunakan BarcodeDetector', function () {
    expect(cameraReliabilitySource('resources/js/scanner.js'))->not->toContain('BarcodeDetector')
        ->and(cameraReliabilitySource('resources/js/components/qr-camera-scanner.js'))->not->toContain('BarcodeDetector');
});

test('camera component memiliki start stop dan retry decoder', function () {
    $scanner = cameraReliabilitySource('resources/js/scanner.js');
    $component = cameraReliabilitySource('resources/js/components/qr-camera-scanner.js');

    expect($scanner)
        ->toContain('async startDecoder()')
        ->toContain('stopDecoder()')
        ->toContain('async retryDecoder()')
        ->toContain('waitForCameraWarmUp')
        ->toContain('applySupportedCameraOptimizations')
        ->toContain('applyTorch');

    expect($component)
        ->toContain('async startDecoder()')
        ->toContain('stopDecoder()')
        ->toContain('async retryDecoder()')
        ->toContain('focusMode')
        ->toContain('exposureMode')
        ->toContain('whiteBalanceMode');
});

test('flow scan validate check-in success tetap berjalan', function () {
    $event = Event::factory()->active()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);

    $ticket = Ticket::factory()->for($category, 'ticketCategory')->create([
        'event_id' => $event->id,
        'code' => 'REL-001',
        'qr_code' => 'REL-001',
    ]);

    $scanner = User::factory()->scanner()->create();
    ScannerEventAssignment::create(['user_id' => $scanner->id, 'event_id' => $event->id, 'assigned_at' => now()]);
    $this->actingAs($scanner);

    $this->postJson(route('scanner.validate'), ['code' => 'rel-001'])
        ->assertOk()
        ->assertJsonPath('status', TicketValidationService::VALID)
        ->assertJsonPath('code', 'REL-001')
        ->assertJsonPath('category', 'VIP');

    $this->postJson(route('scanner.check-in'), ['code' => 'rel-001'])
        ->assertOk()
        ->assertJsonPath('status', TicketCheckInService::SUCCESS);

    expect($ticket->fresh()->checked_in_at)->not->toBeNull();
});
