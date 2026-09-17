<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use App\Models\TicketCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Kategori Tiket')]
class TicketCategories extends Component
{
    public ?int $eventFilter = null;

    public bool $showModal = false;

    public ?int $editingId = null;

    public ?int $event_id = null;

    public string $name = '';

    public bool $is_active = true;

    public function create(): void
    {
        $this->resetForm();

        $this->event_id = $this->eventFilter;
        $this->showModal = true;
    }

    public function edit(int $categoryId): void
    {
        $category = TicketCategory::findOrFail($categoryId);

        $this->editingId = $category->id;
        $this->event_id = $category->event_id;
        $this->name = $category->name;
        $this->is_active = $category->is_active;

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            TicketCategory::findOrFail($this->editingId)->update($validated);
        } else {
            TicketCategory::create($validated);
        }

        $this->closeModal();
    }

    public function toggleActive(int $categoryId): void
    {
        $category = TicketCategory::findOrFail($categoryId);

        $category->update(['is_active' => ! $category->is_active]);
    }

    public function closeModal(): void
    {
        $this->showModal = false;

        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->event_id = null;
        $this->name = '';
        $this->is_active = true;

        $this->resetValidation();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'event_id' => ['required', 'integer', Rule::exists('events', 'id')],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ticket_categories', 'name')
                    ->where('event_id', $this->event_id)
                    ->ignore($this->editingId),
            ],
            'is_active' => ['boolean'],
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.ticket-categories', [
            'events' => Event::query()->orderBy('name')->get(),
            'categories' => TicketCategory::query()
                ->with('event')
                ->when($this->eventFilter, fn ($query) => $query->where('event_id', $this->eventFilter))
                ->orderBy('name')
                ->get(),
        ])->layoutData([
            'topbarTitle' => 'Kategori Tiket',
            'topbarSubtitle' => 'Kelola kategori tiket untuk setiap event',
        ]);
    }
}
