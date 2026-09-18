<?php

use App\Livewire\Admin\Settings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('guest redirect', function () {
    $this->get(route('admin.settings'))
        ->assertRedirect(route('login'));
});

test('scanner forbidden', function () {
    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('admin.settings'))
        ->assertForbidden();
});

test('admin access', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('Pengaturan Aplikasi')
        ->assertSee('GateFlow');
});

test('update setting', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(Settings::class)
        ->set('appName', 'Scan Event')
        ->set('scannerSuccessTimeout', 3000)
        ->set('systemTimezone', 'Asia/Makassar')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Pengaturan berhasil disimpan.');
});

test('value tersimpan database', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(Settings::class)
        ->set('appName', 'Gate Ops')
        ->set('scannerSuccessTimeout', 2500)
        ->set('systemTimezone', 'Asia/Jayapura')
        ->call('save');

    expect(Setting::query()->where('key', 'app.name')->value('value'))->toBe('Gate Ops')
        ->and(Setting::query()->where('key', 'scanner.success_timeout')->value('value'))->toBe('2500')
        ->and(Setting::query()->where('key', 'system.timezone')->value('value'))->toBe('Asia/Jayapura');
});

test('sidebar menggunakan nama aplikasi dari setting', function () {
    Setting::query()->create([
        'key' => 'app.name',
        'value' => 'App Scanner',
        'type' => 'string',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('App Scanner')
        ->assertSee('data-testid="application-name"', false);
});

test('root redirect berdasarkan role', function () {
    $this->get('/')
        ->assertRedirect(route('login'));

    $this->actingAs(User::factory()->admin()->create())
        ->get('/')
        ->assertRedirect(route('admin.dashboard'));

    $this->actingAs(User::factory()->scanner()->create())
        ->get('/')
        ->assertRedirect(route('scanner.index'));
});
