<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_scanner(): void
    {
        $this->get('/scanner')->assertRedirect(route('login'));
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_admin_can_access_scanner(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/scanner')
            ->assertOk();
    }

    public function test_scanner_gets_forbidden_for_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->scanner()->create())
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_scanner_can_access_scanner(): void
    {
        $this->actingAs(User::factory()->scanner()->create())
            ->get('/scanner')
            ->assertOk();
    }

    public function test_login_admin_redirects_to_admin_dashboard(): void
    {
        $user = User::factory()->admin()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/admin/dashboard');
    }

    public function test_login_scanner_redirects_to_scanner(): void
    {
        $user = User::factory()->scanner()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/scanner');
    }

    public function test_public_registration_is_not_available(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->assertFalse(Route::has('register.store'));
    }

    public function test_role_cast_uses_user_role_enum(): void
    {
        $user = User::factory()->scanner()->create();

        $this->assertInstanceOf(UserRole::class, $user->role);
        $this->assertTrue($user->isScanner());
        $this->assertFalse($user->isAdmin());
    }
}
