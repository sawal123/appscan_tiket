<?php

use App\Livewire\Admin\Dashboard as AdminDashboard;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', 'admin/dashboard')->name('dashboard');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::livewire('admin/dashboard', AdminDashboard::class)->name('admin.dashboard');
});

Route::view('scanner', 'scanner.index')
    ->middleware(['auth', 'role:admin,scanner'])
    ->name('scanner.index');

require __DIR__.'/settings.php';
