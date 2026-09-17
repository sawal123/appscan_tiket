@props([
    'title' => 'Pilih perangkat',
    'eyebrow' => 'Metode Pemindaian',
])

<section class="scan-method" aria-labelledby="scan-method-title">
    <div class="section-heading">
        <div>
            <p class="eyebrow">{{ $eyebrow }}</p>
            <h2 id="scan-method-title" data-testid="scan-method-title">{{ $title }}</h2>
        </div>
        <button class="text-button" type="button" data-testid="manual-input-open-button" x-on:click="openManual()">
            <i data-lucide="keyboard"></i> Input manual
        </button>
    </div>

    <div class="segmented-control" x-bind:class="{ 'is-device': mode === 'device' }" role="tablist" aria-label="Metode scan" data-testid="scan-method-tabs">
        <span class="segmented-indicator" aria-hidden="true"></span>

        <button class="segment" type="button" role="tab" data-mode="camera" data-testid="camera-mode-tab" x-bind:class="{ 'is-active': mode === 'camera' }" x-bind:aria-selected="(mode === 'camera').toString()" x-on:click="setMode('camera')">
            <i data-lucide="camera"></i>Camera
        </button>

        <button class="segment" type="button" role="tab" data-mode="device" data-testid="device-mode-tab" x-bind:class="{ 'is-active': mode === 'device' }" x-bind:aria-selected="(mode === 'device').toString()" x-on:click="setMode('device')">
            <i data-lucide="scan-barcode"></i>Scanner Device
        </button>
    </div>

    <div class="scanner-stage" data-testid="scanner-stage">
        {{ $slot }}
    </div>
</section>
