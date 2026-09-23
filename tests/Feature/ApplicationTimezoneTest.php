<?php

use App\Models\Setting;
use App\Services\ApplicationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('application defaults to Jakarta timezone', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta')
        ->and(date_default_timezone_get())->toBe('Asia/Jakarta');
});

test('database timezone setting is applied to application timezone', function () {
    Setting::query()->create([
        'key' => 'system.timezone',
        'value' => 'Asia/Makassar',
        'type' => 'string',
    ]);

    app(ApplicationSettings::class)->applyTimezone();

    expect(config('app.timezone'))->toBe('Asia/Makassar')
        ->and(date_default_timezone_get())->toBe('Asia/Makassar');
});
