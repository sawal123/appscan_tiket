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

    public bool $showModal = false;

    public ?int $event_id = null;

    public ?int $ticket_category_id = null;

    public string $qr_code = '';

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

    public function updatedEventId(): void
    {
        $this->ticket_category_id = null;
    }

    public function create(): void
    {
        $this->resetForm();

        $this->event_id = $this->eventFilter;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();
        $qrCode = Ticket::normalizeQrCode($validated['qr_code']);

        Ticket::create([
            'event_id' => $validated['event_id'],
            'ticket_category_id' => $validated['ticket_category_id'],
            'code' => $qrCode,
            'qr_code' => $qrCode,
            'status' => Ticket::STATUS_REGISTERED,
            'registered_at' => now(),
            'registered_by' => auth()->id(),
        ]);

        $this->closeModal();
    }

    public function import(TicketImportService $importService): void
    {
        $validated = $this->validate([
            'import_event_id' => ['required', 'integer', Rule::exists('events', 'id')],
            'importFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $this->importResult = $importService->import(
            (int) $validated['import_event_id'],
            $this->importFile,
            auth()->user(),
        );

        $this->reset('importFile');
    }

    public function closeModal(): void
    {
        $this->showModal = false;

        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->event_id = null;
        $this->ticket_category_id = null;
        $this->qr_code = '';

        $this->resetValidation();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'event_id' => ['required', 'integer', Rule::exists('events', 'id')],
            'ticket_category_id' => [
                'required',
                'integer',
                Rule::exists('ticket_categories', 'id')->where('event_id', $this->event_id),
            ],
            'qr_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tickets', 'qr_code'),
            ],
        ];
    }

    public function render(): View
    {
        $events = Event::query()->orderBy('name')->get();
        $categoryEventId = $this->event_id ?: $this->eventFilter;

        return view('livewire.admin.tickets', [
            'events' => $events,
            'categories' => TicketCategory::query()
                ->with('event')
                ->when($categoryEventId, fn ($query) => $query->where('event_id', $categoryEventId))
                ->orderBy('name')
                ->get(),
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
