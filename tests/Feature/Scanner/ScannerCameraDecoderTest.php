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

function cameraDecoderSource(string $relativePath): string
{
    $path = base_path($relativePath);

    expect(file_exists($path))->toBeTrue();

    return (string) file_get_contents($path);
}

test('scanner.js tidak memakai BarcodeDetector', function () {
    $source = cameraDecoderSource('resources/js/scanner.js');

    expect($source)->not->toContain('BarcodeDetector');
});

test('decoder kamera scanner memakai ZXing', function () {
    $scanner = cameraDecoderSource('resources/js/scanner.js');
    $reader = cameraDecoderSource('resources/js/components/qr-camera-scanner.js');

    // The scanner page decodes the frames of the video element it already owns
    // through the shared ZXing reader.
    expect($scanner)
        ->toContain('createQrCodeReader')
        ->toContain('./components/qr-camera-scanner')
        ->toContain('decodeFromVideoElement');

    expect($reader)
        ->toContain('@zxing/browser')
        ->toContain('BrowserQRCodeReader')
        ->toContain('decodeFromConstraints');
});

test('flow scan validate check-in tetap berjalan', function () {
    $event = Event::factory()->active()->create();
    $category = TicketCategory::factory()->for($event)->create(['name' => 'VIP']);

    $ticket = Ticket::factory()->for($category, 'ticketCategory')->create([
        'event_id' => $event->id,
        'code' => 'CAM-001',
        'qr_code' => 'CAM-001',
    ]);

    $this->actingAs(User::factory()->scanner()->create());

    $this->postJson(route('scanner.validate'), ['code' => 'cam-001'])
        ->assertOk()
        ->assertJsonPath('status', TicketValidationService::VALID)
        ->assertJsonPath('code', 'CAM-001')
        ->assertJsonPath('category', 'VIP');

    $this->postJson(route('scanner.check-in'), ['code' => 'cam-001'])
        ->assertOk()
        ->assertJsonPath('status', TicketCheckInService::SUCCESS);

    expect($ticket->fresh()->checked_in_at)->not->toBeNull();
});
