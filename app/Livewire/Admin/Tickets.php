<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Services\TicketImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
#[Title('Tiket')]
class Tickets extends Component
{
    use WithFileUploads;

    public string $search = '';

    public ?int $eventFilter = null;

    public ?int $categoryFilter = null;

    public ?int $import_event_id = null;

    public ?TemporaryUploadedFile $importFile = null;

    /**
     * @var array{success: int, failed: int, errors: array<int, string>}|null
     */
    public ?array $importResult = null;

    public function updatedEventFilter(): void
    {
        $this->categoryFilter = null;
    }

    public function import(TicketImportService $importService): void
    {
        $validated = $this->validate([
            'import_event_id' => ['required', 'integer', Rule::exists('events', 'id')],
            'importFile' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:2048'],
        ]);

        $this->importResult = $importService->import(
            (int) $validated['import_event_id'],
            $this->importFile,
            auth()->user(),
        );

        $this->reset('importFile');
    }

    public function render(): View
    {
        return view('livewire.admin.tickets', [
            'events' => Event::query()->orderBy('name')->get(),
            'filterCategories' => TicketCategory::query()
                ->when($this->eventFilter, fn ($query) => $query->where('event_id', $this->eventFilter))
                ->orderBy('name')
                ->get(),
            'tickets' => Ticket::query()
                ->with(['event', 'ticketCategory', 'registeredBy'])
                ->when($this->search !== '', function ($query): void {
                    $search = '%'.Ticket::normalizeQrCode($this->search).'%';

                    $query->where(function ($query) use ($search): void {
                        $query->where('qr_code', 'like', $search)->orWhere('code', 'like', $search);
                    });
                })
                ->when($this->eventFilter, fn ($query) => $query->where('event_id', $this->eventFilter))
                ->when($this->categoryFilter, fn ($query) => $query->where('ticket_category_id', $this->categoryFilter))
                ->latest()
                ->get(),
        ])->layoutData([
            'topbarTitle' => 'Tiket',
            'topbarSubtitle' => 'Registrasi tiket manual dan import QR',
        ]);
    }
}
