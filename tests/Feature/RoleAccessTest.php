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

    public function test_inactive_scanner_cannot_access_scanner(): void
    {
        $this->actingAs(User::factory()->scanner()->create(['is_active' => false]))
            ->get('/scanner')
            ->assertForbidden();
    }

    public function test_inactive_scanner_cannot_access_other_scanner_routes(): void
    {
        $inactive = User::factory()->scanner()->create(['is_active' => false]);

        $this->actingAs($inactive)
            ->get('/scanner/verified')
            ->assertForbidden();

        $this->actingAs($inactive)
            ->postJson(route('scanner.validate'), ['code' => 'ANY-001'])
            ->assertForbidden();

        $this->actingAs($inactive)
            ->postJson(route('scanner.check-in'), ['code' => 'ANY-001'])
            ->assertForbidden();
    }

    public function test_active_scanner_is_not_affected(): void
    {
        $this->actingAs(User::factory()->scanner()->create(['is_active' => true]))
            ->get('/scanner')
            ->assertOk();
    }

    public function test_admin_is_not_affected_by_inactive_status(): void
    {
        $this->actingAs(User::factory()->admin()->create(['is_active' => false]))
            ->get('/admin/dashboard')
            ->assertOk();

        $this->actingAs(User::factory()->admin()->create(['is_active' => false]))
            ->get('/scanner')
            ->assertOk();
    }
}
