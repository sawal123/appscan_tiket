<section class="sheet-backdrop" id="resultBackdrop" data-testid="result-sheet-backdrop" x-show="sheet !== null" x-cloak x-on:click.self="closeSheet()">
    <article class="ticket-result" id="ticketResult" role="dialog" aria-modal="true" aria-label="Hasil pemindaian tiket" data-testid="ticket-result-sheet">
        <div class="sheet-handle" aria-hidden="true"></div>

        <div id="resultContent" data-testid="ticket-result-content">
            <div x-show="sheet === 'validating'" data-testid="validating-state">
                <div class="result-status result-status--valid">
                    <span class="result-status__icon"><span class="spinner"></span></span>
                    <span class="result-badge result-badge--muted" data-testid="validating-status-badge">MEMERIKSA</span>
                    <h2 data-testid="validating-title">Memeriksa tiket...</h2>
                    <p class="result-status__description" data-testid="validating-code" x-text="result.code"></p>
                </div>
            </div>

            <div x-show="sheet === 'valid'" data-testid="valid-ticket-state">
                <div class="result-status result-status--valid">
                    <span class="result-status__icon"><i data-lucide="badge-check"></i></span>
                    <span class="result-badge result-badge--success" data-testid="valid-status-badge">TIKET VALID</span>
                    <h2 data-testid="valid-ticket-title">Tiket Valid</h2>
                    <p class="result-status__description" data-testid="valid-ticket-description" x-text="result.message ?? 'Tiket aktif dan belum digunakan.'">Tiket aktif dan belum digunakan.</p>
                </div>

                <dl class="result-details" data-testid="valid-ticket-details">
                    <div class="detail-wide"><dt>QR</dt><dd data-testid="valid-ticket-code" x-text="result.code"></dd></div>
                    <div><dt>Kategori</dt><dd data-testid="valid-ticket-category" x-text="result.category ?? '-'"></dd></div>
                    <div><dt>Status</dt><dd data-testid="valid-ticket-status">Belum Terverifikasi</dd></div>
                    <div class="detail-wide"><dt>Event</dt><dd data-testid="valid-ticket-event" x-text="result.event ?? '-'"></dd></div>
                </dl>

                <div class="result-actions">
                    <button class="button button--primary button--large" type="button" data-testid="confirm-ticket-button" x-bind:disabled="confirming" x-on:click="confirmCheckIn()">
                        <span class="spinner" x-show="confirming" aria-hidden="true"></span>
                        <span x-show="! confirming" x-cloak><i data-lucide="check"></i></span>
                        <span x-text="confirming ? 'Memverifikasi...' : 'Konfirmasi Masuk'">Konfirmasi Masuk</span>
                    </button>
                    <button class="button button--secondary button--large" type="button" data-testid="cancel-ticket-button" x-on:click="closeSheet()">Batalkan</button>
                </div>
            </div>

            <div x-show="sheet === 'success'" data-testid="success-ticket-state">
                <div class="result-status result-status--success">
                    <span class="result-status__icon"><i data-lucide="circle-check-big"></i></span>
                    <span class="result-badge result-badge--success" data-testid="success-status-badge">BERHASIL</span>
                    <h2 data-testid="success-ticket-title">Check-in berhasil</h2>
                    <p class="result-status__description" data-testid="success-ticket-description" x-text="result.message ?? 'Check-in berhasil. Siap untuk tiket berikutnya.'">Check-in berhasil. Siap untuk tiket berikutnya.</p>
                </div>

                <dl class="result-details" data-testid="success-ticket-details">
                    <div class="detail-wide"><dt>QR</dt><dd data-testid="success-ticket-code" x-text="result.code ?? '-'"></dd></div>
                    <div><dt>Kategori</dt><dd data-testid="success-ticket-category" x-text="result.category ?? '-'"></dd></div>
                    <div><dt>Scanner</dt><dd data-testid="success-ticket-scanner" x-text="result.scanner ?? '-'"></dd></div>
                    <div class="detail-wide"><dt>Waktu check-in</dt><dd data-testid="success-ticket-time" x-text="checkedInLabel"></dd></div>
                </dl>

                <div class="result-actions">
                    <button class="button button--primary button--large" type="button" data-testid="scan-next-success-button" x-on:click="closeSheet()"><i data-lucide="scan-line"></i>Scan Tiket Berikutnya</button>
                </div>
            </div>

            <div x-show="sheet === 'used'" data-testid="used-ticket-state">
                <div class="result-status result-status--warning">
                    <span class="result-status__icon"><i data-lucide="history"></i></span>
                    <span class="result-badge result-badge--warning" data-testid="used-status-badge">SUDAH DIGUNAKAN</span>
                    <h2 data-testid="used-ticket-title">Tiket sudah digunakan</h2>
                    <p class="result-status__description" data-testid="used-ticket-description" x-text="result.message ?? 'Tiket ini sudah digunakan sebelumnya.'">Tiket ini sudah digunakan sebelumnya.</p>
                </div>

                <dl class="result-details" data-testid="used-ticket-details">
                    <div class="detail-wide"><dt>QR</dt><dd data-testid="used-ticket-code" x-text="result.code ?? '-'"></dd></div>
                    <div><dt>Kategori</dt><dd data-testid="used-ticket-category" x-text="result.category ?? '-'"></dd></div>
                    <div><dt>Scanner sebelumnya</dt><dd data-testid="used-ticket-scanner" x-text="result.scanner ?? '-'"></dd></div>
                    <div class="detail-wide"><dt>Waktu check-in sebelumnya</dt><dd data-testid="used-ticket-time" x-text="checkedInLabel"></dd></div>
                </dl>

                <div class="result-actions">
                    <button class="button button--primary button--large" type="button" data-testid="scan-next-used-button" x-on:click="closeSheet()"><i data-lucide="scan-line"></i>Scan Tiket Berikutnya</button>
                </div>
            </div>

            <div x-show="sheet === 'invalid'" data-testid="invalid-ticket-state">
                <div class="result-status result-status--danger">
                    <span class="result-status__icon"><i data-lucide="circle-x"></i></span>
                    <span class="result-badge result-badge--danger" data-testid="invalid-status-badge">TIKET TIDAK VALID</span>
                    <h2 data-testid="invalid-ticket-title">Tiket tidak ditemukan</h2>
                    <p class="result-status__description" data-testid="invalid-ticket-message" x-text="result.message ?? 'QR ini tidak terdaftar pada event aktif.'">QR ini tidak terdaftar pada event aktif.</p>
                </div>

                <dl class="result-details" data-testid="invalid-ticket-details">
                    <div class="detail-wide"><dt>QR</dt><dd data-testid="invalid-ticket-code" x-text="result.code"></dd></div>
                </dl>

                <div class="result-actions">
                    <button class="button button--primary button--large" type="button" data-testid="rescan-invalid-button" x-on:click="closeSheet()"><i data-lucide="scan-line"></i>Scan Tiket Berikutnya</button>
                    <button class="button button--secondary button--large" type="button" data-testid="invalid-manual-input-button" x-on:click="closeSheet(); openManual()"><i data-lucide="keyboard"></i>Masukkan QR Manual</button>
                </div>
            </div>

            <div x-show="sheet === 'offline'" data-testid="offline-state">
                <div class="result-status result-status--offline">
                    <span class="result-status__icon"><i data-lucide="wifi-off"></i></span>
                    <span class="result-badge result-badge--muted" data-testid="offline-status-badge">TIDAK TERHUBUNG</span>
                    <h2 data-testid="offline-title">Koneksi Terputus</h2>
                    <p class="result-status__description" data-testid="offline-description">Periksa koneksi internet sebelum melanjutkan proses check-in.</p>
                </div>

                <div class="result-actions">
                    <button class="button button--primary button--large" type="button" data-testid="retry-connection-button" x-on:click="closeSheet()"><i data-lucide="refresh-cw"></i>Coba Lagi</button>
                </div>
            </div>
        </div>
    </article>
</section>
