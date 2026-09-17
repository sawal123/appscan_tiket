<section class="modal-backdrop" id="manualModal" data-testid="manual-input-modal" x-show="manualOpen" x-cloak x-on:click.self="closeManual()">
    <article class="modal-card" role="dialog" aria-modal="true" aria-labelledby="manualTitle">
        <div class="modal-header">
            <div>
                <p class="eyebrow">Pencarian Tiket</p>
                <h2 id="manualTitle" data-testid="manual-modal-title">Masukkan QR Manual</h2>
            </div>
            <button class="icon-button" type="button" id="manualClose" aria-label="Tutup input manual" data-testid="manual-modal-close-button" x-on:click="closeManual()"><i data-lucide="x"></i></button>
        </div>

        <form id="manualForm" data-testid="manual-qr-form" x-on:submit.prevent="submitManual()">
            <div class="form-field" x-bind:class="{ 'has-error': manualError !== '' }">
                <label for="manualCode">Kode QR</label>
                <div class="input-shell">
                    <i data-lucide="qr-code"></i>
                    <input id="manualCode" type="text" autocomplete="off" placeholder="Contoh: VIP-001" data-testid="manual-qr-input" required x-ref="manualInput" x-model="manualCode">
                </div>
                <span class="field-message" id="manualError" data-testid="manual-qr-error" x-text="manualError"></span>
            </div>

            <button class="button button--primary button--large" type="submit" data-testid="manual-check-button">
                <span>Periksa Tiket</span><i data-lucide="arrow-right"></i>
            </button>
        </form>
    </article>
</section>
