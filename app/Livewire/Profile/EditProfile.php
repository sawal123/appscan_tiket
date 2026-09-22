<?php

namespace App\Livewire\Profile;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profil Saya')]
class EditProfile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $status = null;

    public function mount(): void
    {
        $user = $this->user();

        $this->name = $user->name;
        $this->email = $user->email;
    }

    /**
     * Update the authenticated user's own name and email.
     */
    public function saveProfile(): void
    {
        $user = $this->user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        $user->forceFill([
            'name' => (string) $validated['name'],
            'email' => (string) $validated['email'],
        ])->save();

        $this->status = 'Informasi akun berhasil diperbarui.';
    }

    /**
     * Update the authenticated user's own password.
     *
     * Empty password fields keep the current password. Otherwise the current
     * password must match before the new password is stored.
     */
    public function updatePassword(): void
    {
        if (blank($this->password) && blank($this->password_confirmation)) {
            $this->reset('current_password', 'password', 'password_confirmation');

            return;
        }

        $validated = $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $this->user();

        if (! Hash::check((string) $validated['current_password'], $user->password)) {
            $this->addError('current_password', 'Password saat ini tidak sesuai.');

            return;
        }

        $user->forceFill(['password' => (string) $validated['password']])->save();

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->status = 'Password berhasil diperbarui.';
    }

    public function render(): View
    {
        $user = $this->user();
        $isScanner = $user->isScanner();

        return view('livewire.profile.edit-profile', [
            'isScanner' => $isScanner,
            'scannerName' => $user->name,
            'scannerInitials' => $user->initials(),
            'scannerRole' => ucfirst($user->role->value),
        ])->layout($isScanner ? 'components.scanner.layout' : 'layouts.admin')->layoutData([
            'title' => 'Profil Saya',
            'description' => 'Kelola informasi akun scanner.',
            'page' => 'profile',
            'topbarTitle' => 'Profil Saya',
            'topbarSubtitle' => 'Kelola informasi akun Anda',
        ]);
    }

    /**
     * Only the authenticated user is ever returned, so this component can
     * never read or write another account.
     */
    private function user(): User
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
