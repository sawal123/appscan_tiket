<?php

use App\Livewire\Admin\TicketCategories;
use App\Models\Event;
use App\Models\Ticket;
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

test('a ticket category without tickets can be deleted', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = TicketCategory::factory()->create();

    Livewire::test(TicketCategories::class)
        ->call('delete', $category->id)
        ->assertHasNoErrors();

    expect(TicketCategory::find($category->id))->toBeNull();
});

test('a ticket category with tickets cannot be deleted', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = TicketCategory::factory()->create();
    Ticket::factory()->for($category, 'ticketCategory')->create();

    Livewire::test(TicketCategories::class)
        ->call('delete', $category->id)
        ->assertHasErrors('delete');

    expect(TicketCategory::find($category->id))->not->toBeNull();
});

test('deleting a ticket category with tickets does not remove it or its tickets', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = TicketCategory::factory()->create();
    $ticket = Ticket::factory()->for($category, 'ticketCategory')->create();

    Livewire::test(TicketCategories::class)
        ->call('delete', $category->id)
        ->assertHasErrors('delete');

    expect(TicketCategory::find($category->id))->not->toBeNull()
        ->and(Ticket::find($ticket->id))->not->toBeNull();
});

test('a ticket category with tickets can be deactivated', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = TicketCategory::factory()->create(['is_active' => true]);
    Ticket::factory()->for($category, 'ticketCategory')->create();

    Livewire::test(TicketCategories::class)
        ->call('toggleActive', $category->id)
        ->assertHasNoErrors();

    expect($category->fresh()->is_active)->toBeFalse();
});

test('existing tickets keep their category after deactivation', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = TicketCategory::factory()->create(['is_active' => true]);
    $ticket = Ticket::factory()->for($category, 'ticketCategory')->create();

    Livewire::test(TicketCategories::class)->call('toggleActive', $category->id);

    expect($ticket->fresh()->ticket_category_id)->toBe($category->id)
        ->and($ticket->fresh()->ticketCategory->is_active)->toBeFalse();
});

test('the ticket categories page hides delete for used categories', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = TicketCategory::factory()->create();
    Ticket::factory()->for($category, 'ticketCategory')->create();

    $this->get(route('admin.ticket-categories'))
        ->assertOk()
        ->assertSee('ticket-category-delete-blocked-'.$category->id, false)
        ->assertDontSee('data-testid="delete-ticket-category-'.$category->id.'"', false);
});

test('a manual delete request for a used category id does not bypass the business rule', function () {
    $this->actingAs(User::factory()->admin()->create());

    $used = TicketCategory::factory()->create();
    Ticket::factory()->for($used, 'ticketCategory')->create();
    $unused = TicketCategory::factory()->create();

    Livewire::test(TicketCategories::class)
        ->call('delete', $used->id)
        ->assertHasErrors('delete');

    Livewire::test(TicketCategories::class)
        ->call('delete', $unused->id)
        ->assertHasNoErrors();

    expect(TicketCategory::find($used->id))->not->toBeNull()
        ->and(TicketCategory::find($unused->id))->toBeNull();
});

test('clicking delete opens the confirmation modal without deleting the category', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = TicketCategory::factory()->create(['name' => 'VIP']);

    Livewire::test(TicketCategories::class)
        ->call('confirmDelete', $category->id)
        ->assertSet('showDeleteModal', true)
        ->assertSet('deletingId', $category->id)
        ->assertSet('deletingName', 'VIP');

    expect(TicketCategory::find($category->id))->not->toBeNull();
});

test('the delete confirmation modal shows the category name and confirmation copy', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = TicketCategory::factory()->create(['name' => 'VVIP']);

    Livewire::test(TicketCategories::class)
        ->call('confirmDelete', $category->id)
        ->assertSet('showDeleteModal', true)
        ->assertSee('Hapus Kategori?')
        ->assertSee('VVIP')
        ->assertSee('Data yang sudah dihapus tidak dapat dikembalikan.');
});

test('cancelling the delete confirmation closes the modal and keeps the category', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = TicketCategory::factory()->create();

    Livewire::test(TicketCategories::class)
        ->call('confirmDelete', $category->id)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingId', null);

    expect(TicketCategory::find($category->id))->not->toBeNull();
});

test('confirming the modal deletes the category and closes the modal', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = TicketCategory::factory()->create();

    Livewire::test(TicketCategories::class)
        ->call('confirmDelete', $category->id)
        ->call('delete', $category->id)
        ->assertHasNoErrors()
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingId', null);

    expect(TicketCategory::find($category->id))->toBeNull();
});

test('a blocked category delete opens the relation modal instead of the confirmation', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = TicketCategory::factory()->create();
    Ticket::factory()->for($category, 'ticketCategory')->create();

    Livewire::test(TicketCategories::class)
        ->call('showDeleteBlocked', $category->id)
        ->assertSet('showDeleteBlockedModal', true)
        ->assertSet('showDeleteModal', false)
        ->assertSee('Kategori Tidak Dapat Dihapus')
        ->assertSee('Kategori ini sudah memiliki tiket yang terhubung.')
        ->assertSee('Nonaktifkan');

    expect(TicketCategory::find($category->id))->not->toBeNull();
});

test('closing the blocked modal resets it', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = TicketCategory::factory()->create();
    Ticket::factory()->for($category, 'ticketCategory')->create();

    Livewire::test(TicketCategories::class)
        ->call('showDeleteBlocked', $category->id)
        ->call('closeDeleteBlocked')
        ->assertSet('showDeleteBlockedModal', false);
});
