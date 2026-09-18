<?php

use App\Http\Controllers\Scanner\ScannerController;
use App\Livewire\Admin\CheckInHistory as AdminCheckInHistory;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Events as AdminEvents;
use App\Livewire\Admin\TicketCategories as AdminTicketCategories;
use App\Livewire\Admin\TicketCreate as AdminTicketCreate;
use App\Livewire\Admin\Tickets as AdminTickets;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', 'admin/dashboard')->name('dashboard');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::livewire('admin/dashboard', AdminDashboard::class)->name('admin.dashboard');
    Route::livewire('admin/events', AdminEvents::class)->name('admin.events');
    Route::livewire('admin/tickets', AdminTickets::class)->name('admin.tickets');
    Route::livewire('admin/tickets/create', AdminTicketCreate::class)->name('admin.tickets.create');
    Route::livewire('admin/ticket-categories', AdminTicketCategories::class)->name('admin.ticket-categories');
    Route::livewire('admin/check-in-history', AdminCheckInHistory::class)->name('admin.check-in-history');
});

Route::middleware(['auth', 'role:admin,scanner'])->prefix('scanner')->group(function () {
    Route::get('/', [ScannerController::class, 'index'])->name('scanner.index');
    Route::get('verified', [ScannerController::class, 'verified'])->name('scanner.verified');
    Route::post('validate', [ScannerController::class, 'validateTicket'])->name('scanner.validate');
    Route::post('check-in', [ScannerController::class, 'checkIn'])->name('scanner.check-in');
});

require __DIR__.'/settings.php';
