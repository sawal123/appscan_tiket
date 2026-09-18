/**
 * Events emitted by the scanner. The component stays Livewire-agnostic so it can
 * be reused anywhere: the page decides what to do with a decoded QR value.
 */
export const QR_CAMERA_SCANNED_EVENT = 'qr-camera:scanned';
export const QR_CAMERA_REARM_EVENT = 'qr-camera:rearm';
export const QR_CAMERA_STOP_EVENT = 'qr-camera:stop';

/**
 * @param {unknown} error
 */
function describeError(error) {
    const name = typeof error?.name === 'string' ? error.name : '';

    if (name === 'NotAllowedError' || name === 'SecurityError') {
        return 'Akses kamera ditolak. Izinkan kamera pada browser.';
    }

    if (name === 'NotFoundError' || name === 'OverconstrainedError') {
        return 'Perangkat kamera tidak ditemukan.';
    }

    if (name === 'NotReadableError') {
        return 'Kamera sedang dipakai aplikasi lain.';
    }

    if (typeof error?.message === 'string' && error.message !== '') {
        return error.message;
    }

    return 'Kamera tidak dapat dijalankan.';
}

/**
 * Reusable Alpine component that owns the whole camera QR lifecycle:
 * start, stop, decode, permission errors, and emitting the decoded value.
 */
export default function qrCameraScanner() {
    return {
        cameraActive: false,
        cameraLoading: false,
        code: '',
        error: '',
        autoRestart: false,
        reader: null,
        controls: null,

        get statusLabel() {
            if (this.error !== '') {
                return 'Kamera tidak tersedia';
            }

            if (this.code !== '') {
                return `QR Code: ${this.code}`;
            }

            if (this.cameraActive) {
                return 'Scanning...';
            }

            if (this.cameraLoading) {
                return 'Mengaktifkan kamera...';
            }

            return 'Kamera belum aktif';
        },

        get statusHint() {
            if (this.error !== '') {
                return this.error;
            }

            if (this.code !== '') {
                return 'QR berhasil dibaca, periksa lalu simpan tiket';
            }

            if (this.cameraActive) {
                return 'Arahkan kamera ke QR tiket';
            }

            return 'Izinkan akses untuk mulai memindai';
        },

        destroy() {
            this.releaseCamera();
        },

        async start() {
            if (this.cameraLoading || this.cameraActive) {
                return;
            }

            this.error = '';
            this.cameraLoading = true;

            try {
                if (! navigator.mediaDevices?.getUserMedia) {
                    throw new Error('Browser ini tidak mendukung akses kamera.');
                }

                // Loaded on demand so the decoder never ships with the admin bundle.
                const { BrowserQRCodeReader } = await import('@zxing/browser');

                this.reader = new BrowserQRCodeReader();

                this.controls = await this.reader.decodeFromConstraints(
                    { video: { facingMode: 'environment' } },
                    this.$refs.cameraVideo,
                    (result) => this.handleResult(result?.getText()),
                );

                this.cameraActive = true;
                this.autoRestart = true;
            } catch (error) {
                this.error = describeError(error);

                this.releaseCamera();
            } finally {
                this.cameraLoading = false;
            }
        },

        stop() {
            this.autoRestart = false;

            this.releaseCamera();
        },

        rearm() {
            const restart = this.autoRestart;

            this.code = '';
            this.error = '';

            if (restart) {
                this.start();
            }
        },

        releaseCamera() {
            this.controls?.stop();
            this.controls = null;
            this.reader = null;
            this.cameraActive = false;
        },

        handleResult(rawValue) {
            const code = String(rawValue ?? '').trim();

            // Ignore empty frames and repeat reads until the form is re-armed after a save.
            if (code === '' || this.code !== '') {
                return;
            }

            this.code = code;

            this.releaseCamera();

            this.$dispatch(QR_CAMERA_SCANNED_EVENT, { code });
        },
    };
}
