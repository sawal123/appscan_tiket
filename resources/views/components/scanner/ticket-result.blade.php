<section class="sheet-backdrop" id="resultBackdrop" data-testid="result-sheet-backdrop" x-show="sheet !== null" x-cloak x-on:click.self="closeSheet()">
    <article class="ticket-result" id="ticketResult" role="dialog" aria-modal="true" aria-label="Hasil pemindaian tiket" data-testid="ticket-result-sheet">
        <div class="sheet-handle" aria-hidden="true"></div>

        <div id="resultContent" data-testid="ticket-result-content">
            <div x-show="sheet === 'validating'" data-testid="validating-state">
                <div class="result-status result-status--valid">
                    <span class="result-status__icon"><span class="spinner"></span></span>
                    <p class="result-status__kicker">Membaca QR</p>
                    <h2 data-testid="validating-title">Memeriksa tiket...</h2>
                    <p class="result-status__description" data-testid="validating-code" x-text="result.code"></p>
                </div>
            </div>

            <div x-show="sheet === 'valid'" data-testid="valid-ticket-state">
                <div class="result-status result-status--valid">
                    <span class="result-status__icon"><i data-lucide="badge-check"></i></span>
                    <p class="result-status__kicker">Tiket Ditemukan</p>
                    <h2 data-testid="valid-ticket-title">Tiket Valid</h2>
                    <p class="result-status__description" data-testid="valid-ticket-description">Tiket aktif dan belum digunakan.</p>
                </div>

                <dl class="result-details" data-testid="valid-ticket-details">
                    <div><dt>Kategori</dt><dd data-testid="valid-ticket-category" x-text="result.category ?? '-'"></dd></div>
                    <div><dt>Status</dt><dd data-testid="valid-ticket-status">Belum Terverifikasi</dd></div>
                    <div class="detail-wide"><dt>Event</dt><dd data-testid="valid-ticket-event" x-text="result.event ?? '-'"></dd></div>
                    <div class="detail-wide"><dt>QR</dt><dd data-testid="valid-ticket-code" x-text="result.code"></dd></div>
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
                    <p class="result-status__kicker">Check-in Selesai</p>
                    <h2 data-testid="success-ticket-title">Check-in berhasil</h2>
                    <p class="result-status__description" data-testid="success-ticket-description">Scanner siap untuk tiket berikutnya.</p>
                </div>

                <dl class="result-details" data-testid="success-ticket-details">
                    <div><dt>Kategori</dt><dd data-testid="success-ticket-category" x-text="result.category ?? '-'"></dd></div>
                    <div><dt>Waktu</dt><dd data-testid="success-ticket-time" x-text="result.checked_in_time ?? '-'"></dd></div>
                    <div><dt>Scanner</dt><dd data-testid="success-ticket-scanner" x-text="result.scanner ?? '-'"></dd></div>
                </dl>

                <div class="result-actions">
                    <button class="button button--primary button--large" type="button" data-testid="scan-next-success-button" x-on:click="closeSheet()"><i data-lucide="scan-line"></i>Scan Tiket Berikutnya</button>
                </div>
            </div>

            <div x-show="sheet === 'used'" data-testid="used-ticket-state">
                <div class="result-status result-status--warning">
                    <span class="result-status__icon"><i data-lucide="history"></i></span>
                    <p class="result-status__kicker">Perlu Perhatian</p>
                    <h2 data-testid="used-ticket-title">Ticket sudah digunakan</h2>
                    <p class="result-status__description" data-testid="used-ticket-description">Tiket ini telah tercatat masuk sebelumnya.</p>
                </div>

                <dl class="result-details" data-testid="used-ticket-details">
                    <div><dt>Kategori</dt><dd data-testid="used-ticket-category" x-text="result.category ?? '-'"></dd></div>
                    <div><dt>Waktu check-in</dt><dd data-testid="used-ticket-time" x-text="usedAtLabel"></dd></div>
                    <div><dt>Scanner sebelumnya</dt><dd data-testid="used-ticket-scanner" x-text="result.scanner ?? '-'"></dd></div>
                </dl>

                <div class="result-actions">
                    <button class="button button--primary button--large" type="button" data-testid="scan-next-used-button" x-on:click="closeSheet()"><i data-lucide="scan-line"></i>Scan Tiket Berikutnya</button>
                </div>
            </div>

            <div x-show="sheet === 'invalid'" data-testid="invalid-ticket-state">
                <div class="result-status result-status--danger">
                    <span class="result-status__icon"><i data-lucide="circle-x"></i></span>
                    <p class="result-status__kicker">QR Tidak Valid</p>
                    <h2 data-testid="invalid-ticket-title">Tiket tidak ditemukan</h2>
                    <p class="result-status__description" data-testid="invalid-ticket-description">QR ini tidak terdaftar pada event aktif.</p>
                </div>

                <dl class="result-details">
                    <div class="detail-wide"><dt>QR</dt><dd data-testid="invalid-ticket-code" x-text="result.code"></dd></div>
                </dl>

                <div class="result-actions">
                    <button class="button button--primary button--large" type="button" data-testid="rescan-invalid-button" x-on:click="closeSheet()"><i data-lucide="refresh-cw"></i>Scan Ulang</button>
                    <button class="button button--secondary button--large" type="button" data-testid="invalid-manual-input-button" x-on:click="closeSheet(); openManual()"><i data-lucide="keyboard"></i>Masukkan QR Manual</button>
                </div>
            </div>

            <div x-show="sheet === 'offline'" data-testid="offline-state">
                <div class="result-status result-status--offline">
                    <span class="result-status__icon"><i data-lucide="wifi-off"></i></span>
                    <p class="result-status__kicker">Sistem Tidak Terhubung</p>
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
