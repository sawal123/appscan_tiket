import qrCameraScanner, { QR_CAMERA_REARM_EVENT, QR_CAMERA_STOP_EVENT } from './components/qr-camera-scanner';

// Admin bundle. Alpine itself is provided by Livewire/Flux, so components are
// registered through the `alpine:init` hook instead of importing Alpine here.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('qrCameraScanner', qrCameraScanner);

    window.Alpine.data('ticketRegistrationScanner', () => ({
        detectedCode: '',
        hardwareCode: '',

        init() {
            this.$watch('$wire.scanMethod', (method) => {
                if (method === 'device') {
                    this.$dispatch(QR_CAMERA_STOP_EVENT);

                    this.focusHardwareInput();
                }
            });

            if (this.$wire.scanMethod === 'device') {
                this.focusHardwareInput();
            }
        },

        onQrScanned(code) {
            this.detectedCode = code;

            this.$wire.set('qr_code', code);
        },

        // Hardware scanners behave as keyboards: the code arrives followed by ENTER.
        submitHardwareCode() {
            const code = this.hardwareCode.trim();

            this.hardwareCode = '';

            if (code === '') {
                return;
            }

            this.detectedCode = code;

            this.$wire.call('register', code);
        },

        rearm() {
            this.detectedCode = '';
            this.hardwareCode = '';

            this.$dispatch(QR_CAMERA_REARM_EVENT);

            if (this.$wire.scanMethod === 'device') {
                this.focusHardwareInput();
            }
        },

        focusHardwareInput() {
            this.$nextTick(() => this.$refs.hardwareInput?.focus());
        },
    }));
});
