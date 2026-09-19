<?php

use App\Enums\UserRole;
use App\Livewire\Profile\EditProfile;
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
    $this->actingAs(User::factory()->create())
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('Profil Saya');
});

test('scanner dapat membuka profile miliknya sendiri', function () {
    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('profile'))
        ->assertOk();
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

test('update profile tidak mengubah role dan status aktif', function () {
    $user = User::factory()->scanner()->create();

    Livewire::actingAs($user)
        ->test(EditProfile::class)
        ->set('name', 'Nama Baru')
        ->set('email', 'scanner-baru@example.com')
        ->call('saveProfile')
        ->assertHasNoErrors();

    $fresh = $user->fresh();

    expect($fresh->role)->toBe(UserRole::Scanner)
        ->and($fresh->is_active)->toBeTrue();
});
