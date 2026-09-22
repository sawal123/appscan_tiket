<?php

use App\Http\Controllers\Scanner\ScannerController;
use App\Livewire\Admin\CheckInHistory as AdminCheckInHistory;
use App\Livewire\Admin\CheckInReport as AdminCheckInReport;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Events as AdminEvents;
use App\Livewire\Admin\ScannerCreate as AdminScannerCreate;
use App\Livewire\Admin\ScannerMonitoring as AdminScannerMonitoring;
use App\Livewire\Admin\ScannerOperations as AdminScannerOperations;
use App\Livewire\Admin\Scanners as AdminScanners;
use App\Livewire\Admin\Settings as AdminSettings;
use App\Livewire\Admin\TicketCategories as AdminTicketCategories;
use App\Livewire\Admin\TicketCreate as AdminTicketCreate;
use App\Livewire\Admin\Tickets as AdminTickets;
use App\Livewire\Profile\EditProfile;
use App\Services\TicketImportService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    if (auth()->user()->isScanner()) {
        return redirect()->route('scanner.index');
    }

    return redirect()->route('admin.dashboard');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', 'admin/dashboard')->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::livewire('profile', EditProfile::class)->name('profile');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::livewire('admin/dashboard', AdminDashboard::class)->name('admin.dashboard');
    Route::livewire('admin/events', AdminEvents::class)->name('admin.events');
    Route::livewire('admin/tickets', AdminTickets::class)->name('admin.tickets');
    Route::get('admin/tickets/import-template', function (TicketImportService $importService) {
        return response($importService->templateXlsx(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="ticket-import-template.xlsx"',
        ]);
    })->name('admin.tickets.import-template');
    Route::livewire('admin/tickets/create', AdminTicketCreate::class)->name('admin.tickets.create');
    Route::livewire('admin/ticket-categories', AdminTicketCategories::class)->name('admin.ticket-categories');
    Route::livewire('admin/check-in-history', AdminCheckInHistory::class)->name('admin.check-in-history');
    Route::livewire('admin/reports/check-in', AdminCheckInReport::class)->name('admin.reports.check-in');
    Route::livewire('admin/scanners', AdminScanners::class)->name('admin.scanners');
    Route::livewire('admin/scanners/create', AdminScannerCreate::class)->name('admin.scanners.create');
    Route::livewire('admin/scanners/monitoring', AdminScannerMonitoring::class)->name('admin.scanners.monitoring');
    Route::livewire('admin/scanners/operations', AdminScannerOperations::class)->name('admin.scanners.operations');
    Route::livewire('admin/settings', AdminSettings::class)->name('admin.settings');
});

Route::middleware(['auth', 'role:admin,scanner'])->prefix('scanner')->group(function () {
    Route::get('/', [ScannerController::class, 'index'])->name('scanner.index');
    Route::get('verified', [ScannerController::class, 'verified'])->name('scanner.verified');
    Route::post('heartbeat', [ScannerController::class, 'heartbeat'])->name('scanner.heartbeat');
    Route::post('validate', [ScannerController::class, 'validateTicket'])->name('scanner.validate');
    Route::post('check-in', [ScannerController::class, 'checkIn'])->name('scanner.check-in');
});

require __DIR__.'/settings.php';
