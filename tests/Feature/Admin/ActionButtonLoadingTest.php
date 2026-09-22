<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('tombol aksi mendapat loading state', function () {
    $this->actingAs(User::factory()->admin()->create());

    Event::factory()->create();

    $this->get(route('admin.events'))
        ->assertOk()
        ->assertSee('admin-button-spinner', false)
        ->assertSee('wire:loading.attr="disabled"', false)
        ->assertSee('wire:loading.class="is-loading cursor-wait opacity-70"', false);
});

test('submit button di-scope ke aksi form-nya', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.events'))
        ->assertOk()
        ->assertSee('wire:target="save"', false);

    $this->get(route('admin.tickets'))
        ->assertOk()
        ->assertSee('wire:target="import"', false);
});

test('target tombol per baris menyertakan id', function () {
    $this->actingAs(User::factory()->admin()->create());

    $first = Event::factory()->create();
    $second = Event::factory()->create();

    $this->get(route('admin.events'))
        ->assertOk()
        ->assertSee('wire:target="edit('.$first->id.')"', false)
        ->assertSee('wire:target="edit('.$second->id.')"', false)
        ->assertSee('wire:target="confirmDelete('.$first->id.')"', false)
        ->assertSee('wire:target="activate('.$first->id.')"', false);
});

test('tombol non-aksi tidak diberi loading state', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-testid="open-scanner-action-button"', false)
        ->assertDontSee('admin-button-spinner', false);
});

test('quick action dashboard mengarah ke route yang sesuai', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('admin.tickets.create').'"', false)
        ->assertSee('href="'.route('admin.ticket-categories').'"', false)
        ->assertSee('href="'.route('admin.scanners.create').'"', false)
        ->assertSee('href="'.route('scanner.index').'"', false);
});
