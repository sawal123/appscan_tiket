// Admin bundle. Alpine itself is provided by Livewire/Flux, so components are
// registered through the `alpine:init` hook instead of importing Alpine here.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('ticketRegistrationScanner', () => ({
        cameraActive: false,
        cameraLoading: false,
        cameraMessage: 'Kamera belum aktif',
        facingMode: 'environment',
        stream: null,
        detector: null,
        detectTimer: null,
        detectedCode: '',
        hardwareCode: '',

        init() {
            this.$watch('$wire.scanMethod', (method) => {
                if (method === 'device') {
                    this.stopCamera();
                    this.focusHardwareInput();
                }
            });

            if (this.$wire.scanMethod === 'device') {
                this.focusHardwareInput();
            }
        },

        destroy() {
            this.stopCamera();
        },

        focusHardwareInput() {
            this.$nextTick(() => this.$refs.hardwareInput?.focus());
        },

        async startCamera() {
            if (this.cameraLoading || this.cameraActive) {
                return;
            }

            if (! navigator.mediaDevices?.getUserMedia) {
                this.cameraMessage = 'Kamera tidak tersedia di browser ini';

                return;
            }

            this.cameraLoading = true;

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode },
                    audio: false,
                });

                this.$refs.cameraVideo.srcObject = this.stream;
                await this.$refs.cameraVideo.play();

                this.cameraActive = true;
                this.startDetector();
            } catch (error) {
                this.cameraMessage = 'Akses kamera ditolak';
            } finally {
                this.cameraLoading = false;
            }
        },

        stopCamera() {
            this.stopDetector();

            if (this.stream) {
                this.stream.getTracks().forEach((track) => track.stop());
                this.stream = null;
            }

            this.cameraActive = false;
        },

        startDetector() {
            this.stopDetector();

            if (typeof window.BarcodeDetector === 'undefined') {
                this.cameraMessage = 'BarcodeDetector tidak didukung, gunakan Hardware Scanner';

                return;
            }

            try {
                this.detector = new window.BarcodeDetector({ formats: ['qr_code'] });
            } catch (error) {
                this.cameraMessage = 'BarcodeDetector tidak dapat dijalankan';

                return;
            }

            const scan = async () => {
                if (! this.cameraActive || ! this.detector) {
                    return;
                }

                if (this.$wire.qr_code === '') {
                    try {
                        const codes = await this.detector.detect(this.$refs.cameraVideo);
                        const value = codes[0]?.rawValue;

                        if (value) {
                            this.capture(value);
                        }
                    } catch (error) {
                        // Individual frames can fail while the video is warming up.
                    }
                }

                this.detectTimer = window.setTimeout(scan, 400);
            };

            scan();
        },

        stopDetector() {
            if (this.detectTimer) {
                window.clearTimeout(this.detectTimer);
                this.detectTimer = null;
            }

            this.detector = null;
        },

        capture(rawCode) {
            const code = String(rawCode).trim();

            if (! code) {
                return;
            }

            this.stopDetector();
            this.detectedCode = code;
            this.$wire.set('qr_code', code);
        },

        submitHardwareCode() {
            const code = this.hardwareCode.trim();
            this.hardwareCode = '';

            if (! code) {
                return;
            }

            this.detectedCode = code;
            this.$wire.call('register', code);
        },

        rearm() {
            this.detectedCode = '';
            this.hardwareCode = '';

            if (this.$wire.scanMethod === 'device') {
                this.focusHardwareInput();
            } else if (this.cameraActive) {
                this.startDetector();
            }
        },
    }));
});
