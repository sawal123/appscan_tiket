<?php

namespace App\Livewire\Admin;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Event')]
class Events extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $event_date = '';

    public string $location = '';

    public string $status = EventStatus::Draft->value;

    public function create(): void
    {
        $this->resetForm();

        $this->showModal = true;
    }

    public function edit(int $eventId): void
    {
        $event = Event::findOrFail($eventId);

        $this->editingId = $event->id;
        $this->name = $event->name;
        $this->event_date = $event->event_date->format('Y-m-d');
        $this->location = $event->location ?? '';
        $this->status = $event->status->value;

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        DB::transaction(function () use ($validated): void {
            $status = EventStatus::from($validated['status']);

            if ($status === EventStatus::Active) {
                $this->completeOtherActiveEvents($this->editingId);
            }

            $attributes = [
                'name' => $validated['name'],
                'event_date' => $validated['event_date'],
                'location' => $validated['location'] ?: null,
                'status' => $status,
            ];

            if ($this->editingId) {
                Event::findOrFail($this->editingId)->update($attributes);
            } else {
                Event::create($attributes);
            }
        });

        $this->closeModal();
    }

    public function activate(int $eventId): void
    {
        $event = Event::findOrFail($eventId);

        DB::transaction(function () use ($event): void {
            $this->completeOtherActiveEvents($event->id);

            $event->update(['status' => EventStatus::Active]);
        });
    }

    public function delete(int $eventId): void
    {
        $event = Event::withCount('ticketCategories')->findOrFail($eventId);

        if ($event->ticket_categories_count > 0) {
            $this->addError('delete', 'Event tidak dapat dihapus karena masih memiliki kategori tiket.');

            return;
        }

        $event->delete();
    }

    public function closeModal(): void
    {
        $this->showModal = false;

        $this->resetForm();
    }

    private function completeOtherActiveEvents(?int $exceptId): void
    {
        Event::query()
            ->where('status', EventStatus::Active->value)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->update(['status' => EventStatus::Completed->value]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->event_date = '';
        $this->location = '';
        $this->status = EventStatus::Draft->value;

        $this->resetValidation();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(EventStatus::class)],
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.events', [
            'events' => Event::query()
                ->withCount('ticketCategories')
                ->orderByDesc('event_date')
                ->get(),
            'statuses' => EventStatus::cases(),
        ])->layoutData([
            'topbarTitle' => 'Event',
            'topbarSubtitle' => 'Kelola daftar event dan statusnya',
        ]);
    }
}
