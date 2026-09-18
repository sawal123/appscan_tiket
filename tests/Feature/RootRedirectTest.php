<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('guest membuka root redirect login', function () {
    $this->get('/')
        ->assertRedirect(route('login'));
});

test('admin membuka root redirect admin dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/')
        ->assertRedirect(route('admin.dashboard'));
});

test('scanner membuka root redirect scanner', function () {
    $this->actingAs(User::factory()->scanner()->create())
        ->get('/')
        ->assertRedirect(route('scanner.index'));
});
