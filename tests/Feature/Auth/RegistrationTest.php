<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_is_not_available(): void
    {
        $this->assertFalse(Route::has('register'));
    }

    public function test_new_users_can_not_register_publicly(): void
    {
        $this->assertFalse(Route::has('register.store'));
    }
}
