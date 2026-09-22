<?php

namespace App\Livewire\Admin;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\ScannerEventAssignment;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Tambah Scanner')]
class ScannerCreate extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?int $event_id = null;

    public function save(): void
    {
        $validated = $this->validate();

        DB::transaction(function () use ($validated): void {
            $scanner = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                // Fixed role: this screen only ever creates scanner accounts.
                'role' => UserRole::Scanner,
                'is_active' => true,
            ]);

            ScannerEventAssignment::create([
                'user_id' => $scanner->id,
                'event_id' => $validated['event_id'],
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ]);
        });

        $this->redirectRoute('admin.scanners');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'event_id' => [
                'required',
                'integer',
                Rule::exists('events', 'id')->whereIn('status', [
                    EventStatus::Draft->value,
                    EventStatus::Active->value,
                ]),
            ],
        ];
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
            'event_id.required' => 'Event scanner wajib dipilih.',
            'event_id.exists' => 'Event scanner tidak valid.',
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.scanner-create', [
            'events' => Event::query()
                ->select(['id', 'name', 'status'])
                ->whereIn('status', [EventStatus::Draft->value, EventStatus::Active->value])
                ->orderBy('name')
                ->get(),
        ])->layoutData([
            'topbarTitle' => 'Tambah Scanner',
            'topbarSubtitle' => 'Buat akun petugas scanner baru',
        ]);
    }
}
