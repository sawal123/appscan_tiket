<?php

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Events as AdminEvents;
use App\Livewire\Admin\TicketCategories as AdminTicketCategories;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', 'admin/dashboard')->name('dashboard');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::livewire('admin/dashboard', AdminDashboard::class)->name('admin.dashboard');
    Route::livewire('admin/events', AdminEvents::class)->name('admin.events');
    Route::livewire('admin/ticket-categories', AdminTicketCategories::class)->name('admin.ticket-categories');
});

Route::view('scanner', 'scanner.index')
    ->middleware(['auth', 'role:admin,scanner'])
    ->name('scanner.index');

require __DIR__.'/settings.php';
