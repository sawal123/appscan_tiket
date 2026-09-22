<?php

use App\Enums\UserRole;
use App\Livewire\Profile\EditProfile;
use App\Models\Event;
use App\Models\ScannerEventAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('guest diarahkan ke login dari halaman profile', function () {
    $this->get(route('profile'))->assertRedirect(route('login'));
});

test('user terautentikasi dapat membuka halaman profile', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('data-testid="admin-sidebar"', false)
        ->assertSee('data-testid="dashboard-title"', false)
        ->assertSee('Profil Saya')
        ->assertSee('data-testid="profile-information-section"', false);
});

test('scanner profile menggunakan layout scanner tanpa navigasi admin', function () {
    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('data-page="profile"', false)
        ->assertSee('data-testid="app-header"', false)
        ->assertSee('data-testid="profile-information-section"', false)
        ->assertSee('data-testid="profile-password-section"', false)
        ->assertDontSee('data-testid="admin-sidebar"', false)
        ->assertDontSee('data-testid="nav-dashboard-link"', false)
        ->assertDontSee('data-testid="nav-event-link"', false)
        ->assertDontSee('data-testid="nav-scanner-users-link"', false)
        ->assertDontSee('data-testid="nav-settings-link"', false)
        ->assertDontSee('Dashboard')
        ->assertDontSee('User Scanner')
        ->assertDontSee('Pengaturan');
});

test('nama dapat diperbarui', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EditProfile::class)
        ->set('name', 'Nama Baru')
        ->call('saveProfile')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Nama Baru');
});

test('email dapat diperbarui', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EditProfile::class)
        ->set('email', 'email-baru@example.com')
        ->call('saveProfile')
        ->assertHasNoErrors();

    expect($user->fresh()->email)->toBe('email-baru@example.com');
});

test('email harus unik', function () {
    User::factory()->create(['email' => 'dipakai@example.com']);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EditProfile::class)
        ->set('email', 'dipakai@example.com')
        ->call('saveProfile')
        ->assertHasErrors(['email']);
});

test('password dapat diubah', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EditProfile::class)
        ->set('current_password', 'password')
        ->set('password', 'password-baru')
        ->set('password_confirmation', 'password-baru')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('password-baru', $user->fresh()->password))->toBeTrue();
});

test('password lama yang salah ditolak', function () {
    $user = User::factory()->create();
    $original = $user->password;

    Livewire::actingAs($user)
        ->test(EditProfile::class)
        ->set('current_password', 'password-salah')
        ->set('password', 'password-baru')
        ->set('password_confirmation', 'password-baru')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);

    expect($user->fresh()->password)->toBe($original);
});

test('field password kosong tidak mengubah password', function () {
    $user = User::factory()->create();
    $original = $user->password;

    Livewire::actingAs($user)
        ->test(EditProfile::class)
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect($user->fresh()->password)->toBe($original);
});

test('konfirmasi password baru harus cocok', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EditProfile::class)
        ->set('current_password', 'password')
        ->set('password', 'password-baru')
        ->set('password_confirmation', 'password-berbeda')
        ->call('updatePassword')
        ->assertHasErrors(['password']);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('update profile tidak mengubah role status aktif dan assignment', function () {
    $user = User::factory()->scanner()->create();
    $event = Event::factory()->active()->create();
    $assignment = ScannerEventAssignment::create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'assigned_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(EditProfile::class)
        ->set('name', 'Nama Baru')
        ->set('email', 'scanner-baru@example.com')
        ->call('saveProfile')
        ->assertHasNoErrors();

    $fresh = $user->fresh();

    expect($fresh->role)->toBe(UserRole::Scanner)
        ->and($fresh->is_active)->toBeTrue()
        ->and($assignment->fresh()->user_id)->toBe($user->id)
        ->and($assignment->fresh()->event_id)->toBe($event->id);
});
