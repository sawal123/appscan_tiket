<?php

use App\Livewire\Admin\TicketCategories;
use App\Models\Event;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('guests are redirected to login from the ticket categories page', function () {
    $this->get(route('admin.ticket-categories'))->assertRedirect(route('login'));
});

test('scanner gets forbidden from the ticket categories page', function () {
    $this->actingAs(User::factory()->scanner()->create())
        ->get(route('admin.ticket-categories'))
        ->assertForbidden();
});

test('admin can open the ticket categories page', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.ticket-categories'))
        ->assertOk()
        ->assertSee('Kategori Tiket');
});

test('admin can create a ticket category', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();

    Livewire::test(TicketCategories::class)
        ->call('create')
        ->set('event_id', $event->id)
        ->set('name', 'VIP')
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(TicketCategory::where('event_id', $event->id)->where('name', 'VIP')->exists())->toBeTrue();
});

test('admin can edit a ticket category', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = TicketCategory::factory()->create(['name' => 'Regular']);

    Livewire::test(TicketCategories::class)
        ->call('edit', $category->id)
        ->set('name', 'VVIP')
        ->call('save')
        ->assertHasNoErrors();

    expect($category->fresh()->name)->toBe('VVIP');
});

test('admin can toggle the active state of a ticket category', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = TicketCategory::factory()->create(['is_active' => true]);

    Livewire::test(TicketCategories::class)->call('toggleActive', $category->id);

    expect($category->fresh()->is_active)->toBeFalse();

    Livewire::test(TicketCategories::class)->call('toggleActive', $category->id);

    expect($category->fresh()->is_active)->toBeTrue();
});

test('a ticket category requires an event', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(TicketCategories::class)
        ->call('create')
        ->set('event_id', null)
        ->set('name', 'Regular')
        ->call('save')
        ->assertHasErrors(['event_id' => 'required']);
});

test('a ticket category name is unique within the same event', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    TicketCategory::factory()->for($event)->create(['name' => 'VIP']);

    Livewire::test(TicketCategories::class)
        ->call('create')
        ->set('event_id', $event->id)
        ->set('name', 'VIP')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);

    expect(TicketCategory::where('event_id', $event->id)->where('name', 'VIP')->count())->toBe(1);
});

test('the same ticket category name is allowed on a different event', function () {
    $this->actingAs(User::factory()->admin()->create());

    $first = Event::factory()->create();
    $second = Event::factory()->create();
    TicketCategory::factory()->for($first)->create(['name' => 'VIP']);

    Livewire::test(TicketCategories::class)
        ->call('create')
        ->set('event_id', $second->id)
        ->set('name', 'VIP')
        ->call('save')
        ->assertHasNoErrors();

    expect(TicketCategory::where('event_id', $second->id)->where('name', 'VIP')->exists())->toBeTrue();
});

test('admin can filter ticket categories by event', function () {
    $this->actingAs(User::factory()->admin()->create());

    $first = Event::factory()->create(['name' => 'Event Satu']);
    $second = Event::factory()->create(['name' => 'Event Dua']);

    TicketCategory::factory()->for($first)->create(['name' => 'Regular']);
    TicketCategory::factory()->for($second)->create(['name' => 'VVIP']);

    Livewire::test(TicketCategories::class)
        ->set('eventFilter', $first->id)
        ->assertSee('Regular')
        ->assertDontSee('VVIP');
});
