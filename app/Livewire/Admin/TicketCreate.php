<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Registrasi QR')]
class TicketCreate extends Component
{
    public ?int $event_id = null;

    public ?int $ticket_category_id = null;

    public string $qr_code = '';

    public string $scanMethod = 'camera';

    public ?string $registeredCode = null;

    public function updatedEventId(): void
    {
        $this->ticket_category_id = null;
    }

    public function updatedQrCode(): void
    {
        $this->qr_code = Ticket::normalizeQrCode($this->qr_code);

        $this->resetValidation('qr_code');
    }

    public function setScanMethod(string $method): void
    {
        $this->scanMethod = $method === 'device' ? 'device' : 'camera';
    }

    public function register(string $code): void
    {
        $this->qr_code = Ticket::normalizeQrCode($code);

        $this->save();
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

        $this->registeredCode = $qrCode;
        $this->qr_code = '';

        $this->resetValidation();

        $this->dispatch('ticket-registered');
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

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'event_id.required' => 'Event wajib dipilih.',
            'event_id.exists' => 'Event tidak valid.',
            'ticket_category_id.required' => 'Kategori wajib dipilih.',
            'ticket_category_id.exists' => 'Kategori tidak valid untuk event ini.',
            'qr_code.required' => 'QR code wajib diisi.',
            'qr_code.unique' => 'QR sudah terdaftar',
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.ticket-create', [
            'events' => Event::query()->orderBy('name')->get(),
            'categories' => TicketCategory::query()
                ->when($this->event_id, fn ($query) => $query->where('event_id', $this->event_id))
                ->orderBy('name')
                ->get(),
        ])->layoutData([
            'topbarTitle' => 'Registrasi QR',
            'topbarSubtitle' => 'Scan QR tiket dan daftarkan ke event',
        ]);
    }
}
