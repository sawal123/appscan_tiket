<?php

use App\Enums\EventStatus;
use App\Livewire\Admin\Events;
use App\Models\Event;
use App\Models\Ticket;
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

test('an event with tickets cannot be deleted', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create();
    Ticket::factory()->for($category, 'ticketCategory')->create(['event_id' => $event->id]);

    Livewire::test(Events::class)
        ->call('delete', $event->id)
        ->assertHasErrors('delete');

    expect(Event::find($event->id))->not->toBeNull();
});

test('deleting an event with tickets does not remove the event or its tickets', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create();
    $ticket = Ticket::factory()->for($category, 'ticketCategory')->create(['event_id' => $event->id]);

    Livewire::test(Events::class)
        ->call('delete', $event->id)
        ->assertHasErrors('delete');

    expect(Event::find($event->id))->not->toBeNull()
        ->and(Ticket::find($ticket->id))->not->toBeNull();
});

test('an event with tickets can be deactivated using the existing status flow', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->active()->create();
    $category = TicketCategory::factory()->for($event)->create();
    Ticket::factory()->for($category, 'ticketCategory')->create(['event_id' => $event->id]);

    Livewire::test(Events::class)
        ->call('edit', $event->id)
        ->set('status', EventStatus::Completed->value)
        ->call('save')
        ->assertHasNoErrors();

    expect($event->fresh()->status)->toBe(EventStatus::Completed);
});

test('tickets and categories remain after the event is deactivated', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->active()->create();
    $category = TicketCategory::factory()->for($event)->create();
    $ticket = Ticket::factory()->for($category, 'ticketCategory')->create(['event_id' => $event->id]);

    Livewire::test(Events::class)
        ->call('edit', $event->id)
        ->set('status', EventStatus::Completed->value)
        ->call('save')
        ->assertHasNoErrors();

    expect($event->fresh()->status)->toBe(EventStatus::Completed)
        ->and(Ticket::find($ticket->id))->not->toBeNull()
        ->and(TicketCategory::find($category->id))->not->toBeNull();
});

test('the events page hides delete for used events and shows a blocked indicator', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create();
    Ticket::factory()->for($category, 'ticketCategory')->create(['event_id' => $event->id]);

    $this->get(route('admin.events'))
        ->assertOk()
        ->assertSee('event-delete-blocked-'.$event->id, false)
        ->assertDontSee('data-testid="delete-event-'.$event->id.'"', false);
});

test('a manual delete request for a used event id does not bypass the business rule', function () {
    $this->actingAs(User::factory()->admin()->create());

    $used = Event::factory()->create();
    $category = TicketCategory::factory()->for($used)->create();
    Ticket::factory()->for($category, 'ticketCategory')->create(['event_id' => $used->id]);
    $unused = Event::factory()->create();

    Livewire::test(Events::class)
        ->call('delete', $used->id)
        ->assertHasErrors('delete');

    Livewire::test(Events::class)
        ->call('delete', $unused->id)
        ->assertHasNoErrors();

    expect(Event::find($used->id))->not->toBeNull()
        ->and(Event::find($unused->id))->toBeNull();
});

test('clicking delete opens the confirmation modal without deleting the event', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create(['name' => 'Festival Kota']);

    Livewire::test(Events::class)
        ->call('confirmDelete', $event->id)
        ->assertSet('showDeleteModal', true)
        ->assertSet('deletingId', $event->id)
        ->assertSet('deletingName', 'Festival Kota');

    expect(Event::find($event->id))->not->toBeNull();
});

test('the delete confirmation modal shows the event name and confirmation copy', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create(['name' => 'Konser Amal']);

    Livewire::test(Events::class)
        ->call('confirmDelete', $event->id)
        ->assertSet('showDeleteModal', true)
        ->assertSee('Hapus Event?')
        ->assertSee('Konser Amal')
        ->assertSee('Data yang sudah dihapus tidak dapat dikembalikan.');
});

test('cancelling the delete confirmation closes the modal and keeps the event', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();

    Livewire::test(Events::class)
        ->call('confirmDelete', $event->id)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingId', null)
        ->assertSet('deletingName', '');

    expect(Event::find($event->id))->not->toBeNull();
});

test('confirming the modal deletes the event and closes the modal', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();

    Livewire::test(Events::class)
        ->call('confirmDelete', $event->id)
        ->call('delete', $event->id)
        ->assertHasNoErrors()
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingId', null);

    expect(Event::find($event->id))->toBeNull();
});

test('opening delete for another event replaces the previous selection', function () {
    $this->actingAs(User::factory()->admin()->create());

    $first = Event::factory()->create(['name' => 'Event A']);
    $second = Event::factory()->create(['name' => 'Event B']);

    Livewire::test(Events::class)
        ->call('confirmDelete', $first->id)
        ->call('cancelDelete')
        ->call('confirmDelete', $second->id)
        ->assertSet('deletingId', $second->id)
        ->assertSet('deletingName', 'Event B');
});

test('a blocked event delete opens the relation modal instead of the confirmation', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create();
    Ticket::factory()->for($category, 'ticketCategory')->create(['event_id' => $event->id]);

    Livewire::test(Events::class)
        ->call('showDeleteBlocked', $event->id)
        ->assertSet('showDeleteBlockedModal', true)
        ->assertSet('showDeleteModal', false)
        ->assertSee('Event Tidak Dapat Dihapus')
        ->assertSee('Nonaktifkan');

    expect(Event::find($event->id))->not->toBeNull();
});

test('a blocked event delete explains the ticket reason', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    $category = TicketCategory::factory()->for($event)->create();
    Ticket::factory()->for($category, 'ticketCategory')->create(['event_id' => $event->id]);

    Livewire::test(Events::class)
        ->call('showDeleteBlocked', $event->id)
        ->assertSet('showDeleteBlockedModal', true)
        ->assertSee('sudah memiliki data tiket yang terhubung');

    expect(Event::find($event->id))->not->toBeNull();
});

test('a blocked event delete due to categories explains the category reason', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    TicketCategory::factory()->for($event)->create();

    Livewire::test(Events::class)
        ->call('showDeleteBlocked', $event->id)
        ->assertSet('showDeleteBlockedModal', true)
        ->assertSee('masih memiliki kategori tiket');

    expect(Event::find($event->id))->not->toBeNull();
});

test('closing the blocked modal resets it', function () {
    $this->actingAs(User::factory()->admin()->create());

    $event = Event::factory()->create();
    TicketCategory::factory()->for($event)->create();

    Livewire::test(Events::class)
        ->call('showDeleteBlocked', $event->id)
        ->call('closeDeleteBlocked')
        ->assertSet('showDeleteBlockedModal', false)
        ->assertSet('deleteBlockedReason', '');
});
