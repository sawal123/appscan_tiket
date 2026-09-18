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
export const CAMERA_WARM_UP_MS = 650;
const DECODER_SCAN_OPTIONS = {
    delayBetweenScanAttempts: 120,
    delayBetweenScanSuccess: 700,
    tryPlayVideoTimeout: 5000,
};

export function createCameraConstraints(facingMode = 'environment') {
    return {
        audio: false,
        video: {
            facingMode: { ideal: facingMode },
            width: { ideal: 1280 },
            height: { ideal: 720 },
            frameRate: { ideal: 30, max: 30 },
        },
    };
}

/**
 * Instantiates the ZXing QR reader. Loaded on demand so the decoder never ships
 * with the initial bundle of either page that scans tickets.
 */
export async function createQrCodeReader() {
    const [{ BarcodeFormat, BrowserQRCodeReader }, { DecodeHintType }] = await Promise.all([
        import('@zxing/browser'),
        import('@zxing/library'),
    ]);

    const hints = new Map();

    hints.set(DecodeHintType.POSSIBLE_FORMATS, [BarcodeFormat.QR_CODE]);
    hints.set(DecodeHintType.TRY_HARDER, true);
    hints.set(DecodeHintType.CHARACTER_SET, 'UTF-8');

    return new BrowserQRCodeReader(hints, DECODER_SCAN_OPTIONS);
}

function sleep(milliseconds) {
    return new Promise((resolve) => {
        window.setTimeout(resolve, milliseconds);
    });
}

export function waitForCameraWarmUp() {
    return sleep(CAMERA_WARM_UP_MS);
}

function getVideoTrack(stream) {
    return stream?.getVideoTracks?.()[0] ?? null;
}

function getTrackCapabilities(track) {
    try {
        return track?.getCapabilities?.() ?? {};
    } catch (error) {
        return {};
    }
}

export async function applySupportedCameraOptimizations(stream) {
    const track = getVideoTrack(stream);

    if (! track?.applyConstraints) {
        return;
    }

    const capabilities = getTrackCapabilities(track);
    const advanced = {};

    if (Array.isArray(capabilities.focusMode) && capabilities.focusMode.includes('continuous')) {
        advanced.focusMode = 'continuous';
    }

    if (Array.isArray(capabilities.exposureMode) && capabilities.exposureMode.includes('continuous')) {
        advanced.exposureMode = 'continuous';
    }

    if (Array.isArray(capabilities.whiteBalanceMode) && capabilities.whiteBalanceMode.includes('continuous')) {
        advanced.whiteBalanceMode = 'continuous';
    }

    if (Object.keys(advanced).length === 0) {
        return;
    }

    try {
        await track.applyConstraints({ advanced: [advanced] });
    } catch (error) {
        // Camera capabilities differ widely; unsupported optimizations are safe to skip.
    }
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
        stream: null,
        decoderStatus: 'Kamera belum aktif',

        get statusLabel() {
            if (this.error !== '') {
                return 'Kamera tidak tersedia';
            }

            if (this.code !== '') {
                return `QR Code: ${this.code}`;
            }

            if (this.cameraActive) {
                return this.decoderStatus;
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
                return this.decoderStatus === 'Mencari QR'
                    ? 'Arahkan kamera ke QR tiket'
                    : 'Tunggu sebentar agar fokus dan cahaya stabil';
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

                this.stream = await navigator.mediaDevices.getUserMedia(createCameraConstraints());
                this.$refs.cameraVideo.srcObject = this.stream;
                await this.$refs.cameraVideo.play();

                await applySupportedCameraOptimizations(this.stream);
                this.decoderStatus = 'Menstabilkan kamera...';
                this.cameraActive = true;

                await sleep(CAMERA_WARM_UP_MS);

                // Loaded on demand so the decoder never ships with the admin bundle.
                this.reader = await createQrCodeReader();
                // The previous implementation used decodeFromConstraints; this keeps
                // stream ownership local so warm-up can finish before ZXing starts.
                this.controls = await this.reader.decodeFromVideoElement(
                    this.$refs.cameraVideo,
                    (result) => this.handleResult(result?.getText()),
                );

                this.decoderStatus = 'Mencari QR';
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

        async startDecoder() {
            await this.start();
        },

        stopDecoder() {
            this.releaseCamera();
        },

        async retryDecoder() {
            this.releaseCamera();
            await this.start();
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
            this.stream?.getTracks?.().forEach((track) => track.stop());
            this.stream = null;
            if (this.$refs.cameraVideo) {
                this.$refs.cameraVideo.srcObject = null;
            }
            this.cameraActive = false;
            this.decoderStatus = 'Kamera belum aktif';
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
            this.decoderStatus = 'QR ditemukan';
            this.lastCode = code;
            this.lockedUntil = Date.now() + SCAN_COOLDOWN_MS;

            this.$dispatch(QR_CAMERA_SCANNED_EVENT, { code });
        },
    };
}
