<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\ScannerEventAssignment;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('User Scanner')]
class Scanners extends Component
{
    public bool $showModal = false;

    public bool $showEventModal = false;

    public ?int $editingId = null;

    public ?int $assigningScannerId = null;

    public string $assigningScannerName = '';

    public ?int $assignmentEventId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $is_active = true;

    public function edit(int $scannerId): void
    {
        $scanner = $this->findScanner($scannerId);

        $this->editingId = $scanner->id;
        $this->name = $scanner->name;
        $this->email = $scanner->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->is_active = $scanner->is_active;

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $validated['is_active'],
        ];

        if (filled($validated['password'] ?? null)) {
            $attributes['password'] = $validated['password'];
        }

        // The role is never part of this form, so editing can never promote
        // a scanner to admin.
        $this->findScanner($this->editingId)->update($attributes);

        $this->closeModal();
    }

    public function toggleActive(int $scannerId): void
    {
        $scanner = $this->findScanner($scannerId);

        $scanner->update(['is_active' => ! $scanner->is_active]);
    }

    public function assignEvent(int $scannerId): void
    {
        $scanner = $this->findScanner($scannerId)->load('scannerEventAssignment');

        $this->assigningScannerId = $scanner->id;
        $this->assigningScannerName = $scanner->name;
        $this->assignmentEventId = $scanner->scannerEventAssignment?->event_id;

        $this->resetValidation();
        $this->showEventModal = true;
    }

    public function saveEventAssignment(): void
    {
        $validated = $this->validateOnlyAssignment();

        ScannerEventAssignment::query()->updateOrCreate(
            ['user_id' => $validated['assigningScannerId']],
            [
                'event_id' => $validated['assignmentEventId'],
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ],
        );

        $this->closeEventModal();
    }

    public function closeModal(): void
    {
        $this->showModal = false;

        $this->resetForm();
    }

    public function closeEventModal(): void
    {
        $this->showEventModal = false;

        $this->assigningScannerId = null;
        $this->assigningScannerName = '';
        $this->assignmentEventId = null;

        $this->resetValidation();
    }

    private function findScanner(?int $scannerId): User
    {
        return User::query()
            ->where('role', UserRole::Scanner->value)
            ->findOrFail($scannerId);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->is_active = true;

        $this->resetValidation();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array{assigningScannerId: int, assignmentEventId: int}
     */
    private function validateOnlyAssignment(): array
    {
        return $this->validate([
            'assigningScannerId' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Scanner->value),
            ],
            'assignmentEventId' => ['required', 'integer', Rule::exists('events', 'id')],
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'email.unique' => 'Email sudah digunakan.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.scanners', [
            'scanners' => User::query()
                ->where('role', UserRole::Scanner->value)
                ->with('scannerEventAssignment.event:id,name')
                ->withCount('checkInLogs')
                ->orderBy('name')
                ->get(),
            'events' => Event::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
        ])->layoutData([
            'topbarTitle' => 'User Scanner',
            'topbarSubtitle' => 'Kelola akun petugas scanner',
        ]);
    }
}
