/**
 * Events emitted by the scanner. The component stays Livewire-agnostic so it can
 * be reused anywhere: the page decides what to do with a decoded QR value.
 */
export const QR_CAMERA_SCANNED_EVENT = 'qr-camera:scanned';
export const QR_CAMERA_REARM_EVENT = 'qr-camera:rearm';
export const QR_CAMERA_RESET_EVENT = 'qr-camera:reset';
export const QR_CAMERA_STOP_EVENT = 'qr-camera:stop';

/**
 * ZXing reports the same QR on many consecutive frames, so a successful read
 * locks the scanner briefly to avoid emitting the same code repeatedly.
 */
const SCAN_COOLDOWN_MS = 1500;

/**
 * Instantiates the ZXing QR reader. Loaded on demand so the decoder never ships
 * with the initial bundle of either page that scans tickets.
 */
export async function createQrCodeReader() {
    const { BrowserQRCodeReader } = await import('@zxing/browser');

    return new BrowserQRCodeReader();
}

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
 *
 * The camera keeps running after a successful read so the operator can scan the
 * next ticket without activating it again.
 */
export default function qrCameraScanner() {
    return {
        cameraActive: false,
        cameraLoading: false,
        code: '',
        error: '',
        lastCode: '',
        lockedUntil: 0,
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
                return 'QR berhasil dibaca, kamera tetap aktif';
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
                this.reader = await createQrCodeReader();

                this.controls = await this.reader.decodeFromConstraints(
                    { video: { facingMode: 'environment' } },
                    this.$refs.cameraVideo,
                    (result) => this.handleResult(result?.getText()),
                );

                this.cameraActive = true;
                this.lastCode = '';
                this.lockedUntil = 0;
            } catch (error) {
                this.error = describeError(error);

                this.releaseCamera();
            } finally {
                this.cameraLoading = false;
            }
        },

        /**
         * Explicit stop: releases the camera, e.g. the operator pressed "Hentikan Kamera".
         */
        stop() {
            this.releaseCamera();
        },

        /**
         * Clears the read result after a ticket was registered. The just registered
         * QR stays suppressed, so it cannot be captured again while it is still in
         * front of the camera, but the camera itself keeps running.
         */
        rearm() {
            this.code = '';
            this.error = '';
        },

        /**
         * Manual "Scan Ulang": clears the result and the duplicate protection so the
         * QR currently in front of the camera can be read again.
         */
        reset() {
            this.code = '';
            this.error = '';
            this.lastCode = '';
            this.lockedUntil = 0;
        },

        releaseCamera() {
            this.controls?.stop();
            this.controls = null;
            this.reader = null;
            this.cameraActive = false;
        },

        handleResult(rawValue) {
            const code = String(rawValue ?? '').trim();

            if (code === '' || ! this.cameraActive) {
                return;
            }

            // One capture per cycle: the result has to be saved or reset first.
            if (this.code !== '') {
                return;
            }

            // Debounce the burst of frames ZXing reports for a single QR.
            if (Date.now() < this.lockedUntil) {
                return;
            }

            // A QR that is still in front of the camera must not emit repeatedly.
            if (code === this.lastCode) {
                return;
            }

            this.code = code;
            this.lastCode = code;
            this.lockedUntil = Date.now() + SCAN_COOLDOWN_MS;

            this.$dispatch(QR_CAMERA_SCANNED_EVENT, { code });
        },
    };
}
