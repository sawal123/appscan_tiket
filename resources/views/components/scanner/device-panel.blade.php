<section class="device-mode" id="deviceMode" aria-label="Pemindai perangkat" data-testid="device-mode-panel" x-show="mode === 'device'" x-cloak>
    <div class="device-ready">
        <div class="device-ready__icon"><i data-lucide="scan-barcode"></i><span class="pulse-ring"></span></div>
        <p class="eyebrow">Perangkat Terhubung</p>
        <h2 data-testid="device-ready-title">Scanner siap</h2>
        <p data-testid="device-ready-description">Scan QR menggunakan perangkat scanner</p>

        <label class="scanner-input-shell" for="deviceInput">
            <i data-lucide="keyboard"></i>
            <input
                id="deviceInput"
                type="text"
                inputmode="text"
                autocomplete="off"
                placeholder="Menunggu QR..."
                data-testid="hardware-scanner-input"
                x-ref="deviceInput"
                x-model="deviceCode"
                x-on:keydown.enter.prevent="submitDeviceInput()"
            >
            <span class="listening-indicator" aria-label="Input aktif"></span>
        </label>

        <small data-testid="device-input-hint">Input otomatis diproses setelah scanner mengirim Enter</small>
    </div>
</section>
