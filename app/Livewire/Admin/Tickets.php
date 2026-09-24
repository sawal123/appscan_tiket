<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Services\TicketImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Tiket')]
class Tickets extends Component
{
    use WithFileUploads, WithPagination;

    public int $perPage = 50;

    public string $search = '';

    public ?int $eventFilter = null;

    public ?int $categoryFilter = null;

    public ?int $import_event_id = null;

    public ?TemporaryUploadedFile $importFile = null;

    /**
     * @var array<int, int|string>
     */
    public array $selectedTicketIds = [];

    public bool $selectAllDisplayed = false;

    public bool $showEditModal = false;

    public ?int $editingTicketId = null;

    public string $editingQrCode = '';

    public ?int $editingCategoryId = null;

    /**
     * @var array<int, array{id: int, name: string}>
     */
    public array $editingCategoryOptions = [];

    public ?string $ticketActionMessage = null;

    public bool $showDeleteModal = false;

    public ?int $deletingTicketId = null;

    public string $deletingTicketLabel = '';

    public bool $showBulkDeleteModal = false;

    /**
     * @var array{success: int, failed: int, errors: array<int, string>}|null
     */
    public ?array $importResult = null;

    public function updatedEventFilter(): void
    {
        $this->categoryFilter = null;
        $this->resetSelection();
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetSelection();
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetSelection();
        $this->resetPage();
    }

    public function updatedSelectedTicketIds(): void
    {
        $eligibleIds = array_flip($this->currentPageEligibleTicketIds());

        $this->selectedTicketIds = array_values(array_filter(
            array_map('intval', $this->selectedTicketIds),
            fn (int $ticketId): bool => isset($eligibleIds[$ticketId]),
        ));

        $this->selectAllDisplayed = count($this->selectedTicketIds) > 0
            && count($this->selectedTicketIds) === count($eligibleIds);
    }

    public function updatedSelectAllDisplayed(bool $selected): void
    {
        $this->selectedTicketIds = $selected ? $this->displayedEligibleTicketIds() : [];
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

    public function edit(int $ticketId): void
    {
        $ticket = Ticket::query()
            ->with('ticketCategory:id,event_id,name')
            ->whereKey($ticketId)
            ->whereNull('checked_in_at')
            ->first();

        if (! $ticket) {
            $this->ticketActionMessage = 'Tiket yang sudah check-in tidak dapat diedit.';

            return;
        }

        $this->editingTicketId = $ticket->id;
        $this->editingQrCode = $ticket->qr_code;
        $this->editingCategoryId = $ticket->ticket_category_id;
        $this->editingCategoryOptions = TicketCategory::query()
            ->where('event_id', $ticket->event_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (TicketCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->all();

        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function saveEdit(): void
    {
        $ticket = Ticket::query()
            ->whereKey($this->editingTicketId)
            ->whereNull('checked_in_at')
            ->first();

        if (! $ticket) {
            $this->closeEditModal();
            $this->ticketActionMessage = 'Tiket yang sudah check-in tidak dapat diedit.';

            return;
        }

        $this->editingQrCode = Ticket::normalizeQrCode($this->editingQrCode);

        $validated = $this->validate([
            'editingQrCode' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tickets', 'qr_code')->ignore($ticket->id),
                Rule::unique('tickets', 'code')->ignore($ticket->id),
            ],
            'editingCategoryId' => [
                'required',
                'integer',
                Rule::exists('ticket_categories', 'id')->where('event_id', $ticket->event_id),
            ],
        ]);

        $qrCode = Ticket::normalizeQrCode((string) $validated['editingQrCode']);

        $ticket->forceFill([
            'qr_code' => $qrCode,
            'code' => $qrCode,
            'ticket_category_id' => (int) $validated['editingCategoryId'],
        ])->save();

        $this->closeEditModal();
        $this->ticketActionMessage = 'Tiket berhasil diperbarui.';
    }

    public function confirmDeleteTicket(int $ticketId): void
    {
        $ticket = Ticket::query()
            ->whereKey($ticketId)
            ->whereNull('checked_in_at')
            ->first();

        if (! $ticket) {
            $this->ticketActionMessage = 'Tiket yang sudah check-in tidak dapat dihapus.';

            return;
        }

        $this->showBulkDeleteModal = false;

        $this->deletingTicketId = $ticket->id;
        $this->deletingTicketLabel = $ticket->qr_code;
        $this->showDeleteModal = true;
    }

    public function cancelDeleteTicket(): void
    {
        $this->resetDeleteState();
    }

    public function confirmBulkDelete(): void
    {
        if (count($this->selectedTicketIds) === 0) {
            return;
        }

        $this->resetDeleteState();
        $this->showBulkDeleteModal = true;
    }

    public function cancelBulkDelete(): void
    {
        $this->resetDeleteState();
    }

    public function deleteTicket(int $ticketId): void
    {
        $deleted = Ticket::query()
            ->whereKey($ticketId)
            ->whereNull('checked_in_at')
            ->delete();

        $this->resetSelection();
        $this->resetDeleteState();
        $this->ticketActionMessage = $deleted > 0
            ? 'Tiket berhasil dihapus.'
            : 'Tiket yang sudah check-in tidak dapat dihapus.';
    }

    public function bulkDelete(): void
    {
        $selectedIds = $this->eligibleSelectedTicketIds();

        if ($selectedIds === []) {
            $this->resetSelection();
            $this->resetDeleteState();
            $this->ticketActionMessage = 'Tidak ada tiket valid yang dapat dihapus.';

            return;
        }

        $deleted = Ticket::query()
            ->whereIn('id', $selectedIds)
            ->whereNull('checked_in_at')
            ->delete();

        $this->resetSelection();
        $this->resetDeleteState();
        $this->ticketActionMessage = $deleted > 0
            ? "{$deleted} tiket berhasil dihapus."
            : 'Tidak ada tiket valid yang dapat dihapus.';
    }

    private function resetDeleteState(): void
    {
        $this->showDeleteModal = false;
        $this->showBulkDeleteModal = false;
        $this->deletingTicketId = null;
        $this->deletingTicketLabel = '';
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingTicketId = null;
        $this->editingQrCode = '';
        $this->editingCategoryId = null;
        $this->editingCategoryOptions = [];

        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.tickets', [
            'events' => Event::query()->orderBy('name')->get(),
            'filterCategories' => TicketCategory::query()
                ->when($this->eventFilter, fn ($query) => $query->where('event_id', $this->eventFilter))
                ->orderBy('name')
                ->get(),
            'tickets' => $this->ticketsQuery()
                ->latest()
                ->paginate($this->perPage),
        ])->layoutData([
            'topbarTitle' => 'Tiket',
            'topbarSubtitle' => 'Registrasi tiket manual dan import QR',
        ]);
    }

    /**
     * @return Builder<Ticket>
     */
    private function ticketsQuery(): Builder
    {
        return Ticket::query()
            ->with(['event', 'ticketCategory', 'registeredBy'])
            ->when($this->search !== '', function (Builder $query): void {
                $search = Ticket::normalizeQrCode($this->search).'%';

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('qr_code', 'like', $search)->orWhere('code', 'like', $search);
                });
            })
            ->when($this->eventFilter, fn (Builder $query) => $query->where('event_id', $this->eventFilter))
            ->when($this->categoryFilter, fn (Builder $query) => $query->where('ticket_category_id', $this->categoryFilter));
    }

    /**
     * @return array<int, int>
     */
    private function currentPageEligibleTicketIds(): array
    {
        return $this->ticketsQuery()
            ->whereNull('checked_in_at')
            ->latest()
            ->forPage($this->getPage(), $this->perPage)
            ->pluck('id')
            ->map(fn (int|string $ticketId): int => (int) $ticketId)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function eligibleSelectedTicketIds(): array
    {
        $selectedIds = array_values(array_filter(
            array_map('intval', $this->selectedTicketIds),
            fn (int $ticketId): bool => $ticketId > 0,
        ));

        if ($selectedIds === []) {
            return [];
        }

        return Ticket::query()
            ->whereIn('id', $selectedIds)
            ->whereNull('checked_in_at')
            ->pluck('id')
            ->map(fn (int|string $ticketId): int => (int) $ticketId)
            ->all();
    }

    private function resetSelection(): void
    {
        $this->selectedTicketIds = [];
        $this->selectAllDisplayed = false;
    }
}
