<?php

namespace App\Livewire\Admin;

use App\Services\ApplicationSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Pengaturan')]
class Settings extends Component
{
    public string $appName = '';

    public int $scannerSuccessTimeout = 2200;

    public string $systemTimezone = 'Asia/Jakarta';

    /**
     * @return array<int, string>
     */
    public function timezoneOptions(): array
    {
        return [
            'Asia/Jakarta',
            'Asia/Makassar',
            'Asia/Jayapura',
            'UTC',
        ];
    }

    public function mount(ApplicationSettings $settings): void
    {
        $values = $settings->values();

        $this->appName = $values['app.name'];
        $this->scannerSuccessTimeout = (int) $values['scanner.success_timeout'];
        $this->systemTimezone = $values['system.timezone'];
    }

    public function save(ApplicationSettings $settings): void
    {
        $validated = $this->validate();

        $settings->set('app.name', $validated['appName']);
        $settings->set('scanner.success_timeout', (string) $validated['scannerSuccessTimeout']);
        $settings->set('system.timezone', $validated['systemTimezone']);
        $settings->applyTimezone();

        session()->flash('settingsSaved', 'Pengaturan berhasil disimpan.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'appName' => ['required', 'string', 'max:255'],
            'scannerSuccessTimeout' => ['required', 'integer', 'min:500', 'max:10000'],
            'systemTimezone' => ['required', 'string', Rule::in($this->timezoneOptions())],
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.settings')->layoutData([
            'topbarTitle' => 'Pengaturan',
            'topbarSubtitle' => 'Konfigurasi dasar aplikasi',
        ]);
    }
}
