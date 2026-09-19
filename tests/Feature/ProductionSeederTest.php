<?php

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function configureProductionAdmin(?string $password = 'password'): void
{
    config()->set('admin.name', 'Admin Produksi');
    config()->set('admin.email', 'admin@produksi.test');
    config()->set('admin.password', $password);
}

test('membuat satu akun admin tanpa data lain', function () {
    configureProductionAdmin();

    $this->seed(ProductionSeeder::class);

    $admin = User::query()->where('email', 'admin@produksi.test')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->name)->toBe('Admin Produksi')
        ->and($admin->role)->toBe(UserRole::Admin)
        ->and($admin->is_active)->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and(Hash::check('password', $admin->password))->toBeTrue();

    expect(User::query()->count())->toBe(1)
        ->and(Event::query()->count())->toBe(0)
        ->and(Ticket::query()->count())->toBe(0);
});

test('idempoten dan tidak mereset password admin yang sudah ada', function () {
    configureProductionAdmin();

    $this->seed(ProductionSeeder::class);
    $passwordHash = User::query()->where('email', 'admin@produksi.test')->value('password');

    config()->set('admin.password', null);
    $this->seed(ProductionSeeder::class);

    expect(User::query()->count())->toBe(1)
        ->and(User::query()->where('email', 'admin@produksi.test')->value('password'))->toBe($passwordHash);
});

test('menghasilkan password acak saat ADMIN_PASSWORD kosong', function () {
    configureProductionAdmin(password: null);

    $this->seed(ProductionSeeder::class);

    $admin = User::query()->where('email', 'admin@produksi.test')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->password)->not->toBeNull();
});

test('gagal saat ADMIN_EMAIL tidak diisi', function () {
    config()->set('admin.email', null);
    config()->set('admin.password', 'password');

    expect(fn() => (new ProductionSeeder)->run())->toThrow(RuntimeException::class);
});
