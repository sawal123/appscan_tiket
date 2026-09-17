<?php

use App\Enums\EventStatus;
use App\Livewire\Admin\Events;
use App\Models\Event;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('guests are redirected to login from the events page', function () {
    $this->get(route('admin.events'))->assertRedirect(route('login'));
});

test('scanner gets forbidden from the events page', function () {
    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('admin.events'))
        ->assertForbidden();
});

test('admin can open the events page', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.events'))
        ->assertOk()
        ->assertSee('Daftar Event');
});

test('admin can create an event', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Events::class)
        ->call('create')
        ->set('name', 'Festival Musik Medan')
        ->set('event_date', '2026-10-01')
        ->set('location', 'Medan')
        ->set('status', EventStatus::Draft->value)
        ->call('save')
        ->assertHasNoErrors();

    expect(Event::where('name', 'Festival Musik Medan')->first())
        ->not->toBeNull()
        ->location->toBe('Medan')
        ->status->toBe(EventStatus::Draft);
});

test('admin can edit an event', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create(['name' => 'Nama Lama', 'location' => 'Medan']);

    Livewire::test(Events::class)
        ->call('edit', $event->id)
        ->set('name', 'Nama Baru')
        ->set('location', 'Jakarta')
        ->call('save')
        ->assertHasNoErrors();

    expect($event->fresh())
        ->name->toBe('Nama Baru')
        ->location->toBe('Jakarta');
});

test('admin can activate an event', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create(['status' => EventStatus::Draft]);

    Livewire::test(Events::class)->call('activate', $event->id);

    expect($event->fresh()->status)->toBe(EventStatus::Active);
});

test('activating an event completes the previously active event', function () {
    $this->actingAs(User::factory()->admin()->create());

    $previous = Event::factory()->create(['status' => EventStatus::Active]);
    $next = Event::factory()->create(['status' => EventStatus::Draft]);

    Livewire::test(Events::class)->call('activate', $next->id);

    expect($previous->fresh()->status)->toBe(EventStatus::Completed)
        ->and($next->fresh()->status)->toBe(EventStatus::Active)
        ->and(Event::where('status', EventStatus::Active)->count())->toBe(1);
});

test('saving an event as active completes the previously active event', function () {
    $this->actingAs(User::factory()->admin()->create());

    $previous = Event::factory()->create(['status' => EventStatus::Active]);

    Livewire::test(Events::class)
        ->call('create')
        ->set('name', 'Event Baru')
        ->set('event_date', '2026-11-01')
        ->set('status', EventStatus::Active->value)
        ->call('save')
        ->assertHasNoErrors();

    expect($previous->fresh()->status)->toBe(EventStatus::Completed)
        ->and(Event::where('status', EventStatus::Active)->count())->toBe(1);
});

test('event validation is enforced', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Events::class)
        ->call('create')
        ->set('name', '')
        ->set('event_date', '')
        ->set('status', 'invalid')
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'event_date' => 'required', 'status']);
});

test('an event with ticket categories cannot be deleted', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    TicketCategory::factory()->for($event)->create(['name' => 'Regular']);

    Livewire::test(Events::class)
        ->call('delete', $event->id)
        ->assertHasErrors('delete');

    expect(Event::find($event->id))->not->toBeNull();
});

test('an event without relations can be deleted', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();

    Livewire::test(Events::class)
        ->call('delete', $event->id)
        ->assertHasNoErrors();

    expect(Event::find($event->id))->toBeNull();
});
