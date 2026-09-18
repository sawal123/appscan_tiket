<?php

namespace App\Services;

use App\Models\Setting;

class ApplicationSettings
{
    /**
     * @var array<string, array{value: string, type: string}>
     */
    private const DEFAULTS = [
        'app.name' => ['value' => 'GateFlow', 'type' => 'string'],
        'scanner.success_timeout' => ['value' => '2200', 'type' => 'integer'],
        'system.timezone' => ['value' => 'Asia/Jakarta', 'type' => 'string'],
    ];

    public function ensureDefaults(): void
    {
        foreach (self::DEFAULTS as $key => $setting) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => $setting['value'], 'type' => $setting['type']],
            );
        }
    }

    public function get(string $key): ?string
    {
        $value = Setting::query()->where('key', $key)->value('value');

        if ($value !== null) {
            return (string) $value;
        }

        return self::DEFAULTS[$key]['value'] ?? null;
    }

    /**
     * @return array{'app.name': string, 'scanner.success_timeout': string, 'system.timezone': string}
     */
    public function values(): array
    {
        $this->ensureDefaults();

        return [
            'app.name' => $this->get('app.name') ?? self::DEFAULTS['app.name']['value'],
            'scanner.success_timeout' => $this->get('scanner.success_timeout') ?? self::DEFAULTS['scanner.success_timeout']['value'],
            'system.timezone' => $this->get('system.timezone') ?? self::DEFAULTS['system.timezone']['value'],
        ];
    }

    public function set(string $key, string $value): void
    {
        $type = self::DEFAULTS[$key]['type'] ?? 'string';

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type],
        );
    }
}
