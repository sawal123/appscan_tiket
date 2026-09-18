import Alpine from 'alpinejs';
import {
    ArrowRight,
    createIcons,
    BadgeCheck,
    CalendarDays,
    Camera,
    Check,
    CircleAlert,
    CircleCheck,
    CircleCheckBig,
    CircleX,
    Eye,
    EyeOff,
    Flashlight,
    History,
    Keyboard,
    LockKeyhole,
    LogOut,
    MapPin,
    Moon,
    QrCode,
    RefreshCw,
    ScanBarcode,
    ScanLine,
    Search,
    SearchX,
    ShieldCheck,
    Sun,
    SwitchCamera,
    TicketCheck,
    TriangleAlert,
    UserRound,
    WifiOff,
    X,
} from 'lucide';
import {
    applySupportedCameraOptimizations,
    createCameraConstraints,
    createQrCodeReader,
    waitForCameraWarmUp,
} from './components/qr-camera-scanner';

const THEME_KEY = 'ticket-scanner-theme';
const SCANNER_DEVICE_KEY = 'gateflow-scanner-device-id';
const HEARTBEAT_INTERVAL_MS = 45000;

// A ticket that was just handled stays ignored for a while, because it is usually
// still in front of the camera. Other tickets can be scanned immediately.
const RESCAN_COOLDOWN_MS = 4000;

// How long the success result stays on screen before the scanner is armed again.
const SUCCESS_SHEET_MS = 2200;

// Only the icons referenced by scanner markup are bundled, keyed by their PascalCase name.
const icons = {
    ArrowRight,
    BadgeCheck,
    CalendarDays,
    Camera,
    Check,
    CircleAlert,
    CircleCheck,
    CircleCheckBig,
    CircleX,
    Eye,
    EyeOff,
    Flashlight,
    History,
    Keyboard,
    LockKeyhole,
    LogOut,
    MapPin,
    Moon,
    QrCode,
    RefreshCw,
    ScanBarcode,
    ScanLine,
    Search,
    SearchX,
    ShieldCheck,
    Sun,
    SwitchCamera,
    TicketCheck,
    TriangleAlert,
    UserRound,
    WifiOff,
    X,
};

function renderIcons() {
    createIcons({ icons, attrs: { 'aria-hidden': 'true' } });
}

window.renderIcons = renderIcons;

document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        dark: document.documentElement.dataset.theme === 'dark',

        toggle() {
            this.dark = !this.dark;
            document.documentElement.dataset.theme = this.dark ? 'dark' : 'light';

            try {
                localStorage.setItem(THEME_KEY, this.dark ? 'dark' : 'light');
            } catch (error) {
                // Storage can be unavailable (private mode); theme still applies for this session.
            }
        },
    });

    Alpine.store('connection', {
        online: navigator.onLine,

        init() {
            window.addEventListener('online', () => {
                this.online = true;
            });

            window.addEventListener('offline', () => {
                this.online = false;
            });
        },
    });

    Alpine.store('toasts', {
        items: [],
        sequence: 0,

        add(message, type = 'success') {
            const id = ++this.sequence;
            const icon = type === 'success' ? 'circle-check' : type === 'warning' ? 'triangle-alert' : 'circle-x';
            const toast = { id, message, icon, type, leaving: false };

            this.items.push(toast);

            window.setTimeout(() => {
                toast.leaving = true;
                window.setTimeout(() => {
                    this.items = this.items.filter((item) => item.id !== id);
                }, 220);
            }, 3000);
        },
    });

    Alpine.data('scannerApp', ({ validateUrl, checkInUrl, heartbeatUrl }) => ({
        mode: 'camera',
        sheet: null,
        result: {},
        busy: false,
        confirming: false,
        detected: false,

        cameraActive: false,
        cameraLoading: false,
        cameraMessageTitle: 'Kamera belum aktif',
        cameraMessageText: 'Izinkan akses untuk mulai memindai',
        decoderStatus: 'Kamera belum aktif',
        facingMode: 'environment',
        flashOn: false,
        torchSupported: false,
        stream: null,
        reader: null,
        controls: null,
        successResetTimer: null,

        manualOpen: false,
        manualCode: '',
        manualError: '',
        lastFocused: null,

        deviceCode: '',

        lastHandledCode: '',
        rescanAfter: 0,
        audioContext: null,
        heartbeatTimer: null,
        deviceId: '',
        deviceName: '',

        get checkedInLabel() {
            const date = this.result?.checked_in_date;
            const time = this.result?.checked_in_time;

            if (! date) {
                return '-';
            }

            return time ? `${date} ${time}` : date;
        },

        init() {
            this.deviceId = this.resolveDeviceId();
            this.deviceName = this.resolveDeviceName();
            this.startHeartbeat();

            window.addEventListener('offline', () => {
                this.sheet = 'offline';
                this.$store.toasts.add('Koneksi internet terputus', 'warning');
            });

            window.addEventListener('online', () => {
                if (this.sheet === 'offline') {
                    this.sheet = null;
                }

                this.$store.toasts.add('Koneksi kembali online', 'success');
            });
        },

        destroy() {
            if (this.heartbeatTimer) {
                window.clearInterval(this.heartbeatTimer);
                this.heartbeatTimer = null;
            }
        },

        resolveDeviceId() {
            try {
                const existing = localStorage.getItem(SCANNER_DEVICE_KEY);

                if (existing) {
                    return existing;
                }

                const id = window.crypto?.randomUUID?.() ?? `scanner-${Date.now()}-${Math.random().toString(16).slice(2)}`;

                localStorage.setItem(SCANNER_DEVICE_KEY, id);

                return id;
            } catch (error) {
                return `scanner-${Date.now()}-${Math.random().toString(16).slice(2)}`;
            }
        },

        resolveDeviceName() {
            const platform = navigator.userAgentData?.platform || navigator.platform || 'Browser';

            return `${platform} Scanner`;
        },

        scannerDevicePayload() {
            return {
                device_id: this.deviceId,
                device_name: this.deviceName,
            };
        },

        startHeartbeat() {
            if (! heartbeatUrl) {
                return;
            }

            this.sendHeartbeat();
            this.heartbeatTimer = window.setInterval(() => this.sendHeartbeat(), HEARTBEAT_INTERVAL_MS);
        },

        async sendHeartbeat() {
            if (! this.$store.connection.online) {
                return;
            }

            try {
                await this.post(heartbeatUrl, this.scannerDevicePayload());
            } catch (error) {
                // Heartbeat is monitoring-only; scanner check-in flow remains usable.
            }
        },

        setMode(mode) {
            this.mode = mode;

            if (mode === 'device') {
                this.$nextTick(() => this.$refs.deviceInput?.focus());
            }
        },

        async startCamera(force = false) {
            if (this.cameraLoading || (this.cameraActive && ! force)) {
                return;
            }

            if (! navigator.mediaDevices?.getUserMedia) {
                this.cameraMessageTitle = 'Kamera tidak tersedia';
                this.cameraMessageText = 'Gunakan Scanner Device atau input manual';
                this.$store.toasts.add('Kamera membutuhkan browser dengan akses aman', 'warning');

                return;
            }

            this.cameraLoading = true;

            try {
                this.stopStream();

                this.stream = await navigator.mediaDevices.getUserMedia(createCameraConstraints(this.facingMode));

                this.$refs.cameraVideo.srcObject = this.stream;
                await this.$refs.cameraVideo.play();
                await applySupportedCameraOptimizations(this.stream);

                this.cameraActive = true;
                this.torchSupported = this.canUseTorch();
                this.decoderStatus = 'Menstabilkan kamera...';
                await waitForCameraWarmUp();

                await this.startDecoder();
                this.$store.toasts.add('Kamera aktif dan siap memindai', 'success');
            } catch (error) {
                this.cameraMessageTitle = 'Akses kamera ditolak';
                this.cameraMessageText = 'Izinkan kamera atau gunakan mode perangkat';
                this.$store.toasts.add('Akses kamera belum diberikan', 'danger');
            } finally {
                this.cameraLoading = false;
            }
        },

        async switchCamera() {
            this.facingMode = this.facingMode === 'environment' ? 'user' : 'environment';

            if (this.stream) {
                await this.startCamera(true);
            } else {
                this.$store.toasts.add('Aktifkan kamera terlebih dahulu', 'warning');
            }
        },

        async toggleFlash() {
            if (! this.torchSupported) {
                return;
            }

            const nextState = ! this.flashOn;

            try {
                await this.applyTorch(nextState);
                this.flashOn = nextState;

                this.$store.toasts.add(
                    this.flashOn ? 'Lampu dinyalakan' : 'Lampu dimatikan',
                    'success',
                );
            } catch (error) {
                this.$store.toasts.add('Lampu tidak tersedia di perangkat ini', 'warning');
            }
        },

        canUseTorch() {
            const track = this.stream?.getVideoTracks?.()[0];

            try {
                return Boolean(track?.getCapabilities?.().torch);
            } catch (error) {
                return false;
            }
        },

        async applyTorch(enabled) {
            const track = this.stream?.getVideoTracks?.()[0];

            if (! track?.applyConstraints) {
                throw new Error('Torch is not supported.');
            }

            await track.applyConstraints({ advanced: [{ torch: enabled }] });
        },

        stopStream() {
            if (this.flashOn) {
                this.applyTorch(false).catch(() => {});
            }

            if (this.stream) {
                this.stream.getTracks().forEach((track) => track.stop());
                this.stream = null;
            }

            this.flashOn = false;
            this.torchSupported = false;
            this.cameraActive = false;
            this.decoderStatus = 'Kamera belum aktif';
            this.stopDecoder();
        },

        /**
         * Start decoding the frames of the video element that this page already owns.
         * ZXing never acquires the stream here, so stopping the decoder leaves the
         * camera running.
         */
        async startDecoder() {
            if (this.controls || ! this.cameraActive) {
                return;
            }

            this.stopDecoder();

            try {
                this.reader = await createQrCodeReader();
                this.decoderStatus = 'Mencari QR';

                this.controls = await this.reader.decodeFromVideoElement(
                    this.$refs.cameraVideo,
                    (result) => this.handleCameraResult(result?.getText()),
                );
            } catch (error) {
                this.reader = null;
                this.controls = null;

                this.cameraMessageTitle = 'Kamera tidak dapat memindai';
                this.cameraMessageText = 'Gunakan Scanner Device atau input manual';
                this.decoderStatus = 'Decoder berhenti';
            }
        },

        handleCameraResult(rawValue) {
            const code = String(rawValue ?? '').trim();

            // Hold scanning while a result sheet is open; the camera keeps running.
            if (code === '' || this.sheet !== null) {
                return;
            }

            this.decoderStatus = 'QR ditemukan';
            this.submitCode(code);
        },

        stopDecoder() {
            this.controls?.stop();
            this.controls = null;
            this.reader = null;
        },

        async retryDecoder() {
            this.stopDecoder();

            if (! this.cameraActive) {
                return;
            }

            this.decoderStatus = 'Mencari QR';
            await this.startDecoder();
        },

        submitDeviceInput() {
            const code = this.deviceCode;
            this.deviceCode = '';

            this.submitCode(code);
        },

        openManual() {
            this.lastFocused = document.activeElement;
            this.manualError = '';
            this.manualOpen = true;

            this.$nextTick(() => this.$refs.manualInput?.focus());
        },

        closeManual() {
            this.manualOpen = false;
            this.manualCode = '';
            this.manualError = '';

            this.refocus();
        },

        submitManual() {
            const code = this.manualCode.trim();

            if (! code) {
                this.manualError = 'Kode QR wajib diisi.';

                return;
            }

            this.closeManual();
            this.submitCode(code);
        },

        async submitCode(rawCode) {
            const code = String(rawCode ?? '').trim();

            if (! code || this.busy) {
                return;
            }

            // Ignore the ticket that was just handled, but only that exact code.
            if (code === this.lastHandledCode && Date.now() < this.rescanAfter) {
                return;
            }

            // Pause decoding while the result sheet is up; closing it resumes.
            this.stopDecoder();

            if (! this.$store.connection.online) {
                this.sheet = 'offline';
                this.$store.toasts.add('Koneksi internet terputus', 'warning');

                return;
            }

            this.busy = true;
            this.result = { code };
            this.sheet = 'validating';
            this.detected = true;

            window.setTimeout(() => {
                this.detected = false;
            }, 400);

            try {
                const result = await this.post(validateUrl, { code, ...this.scannerDevicePayload() });
                this.result = result;

                if (result.status === 'valid') {
                    this.sheet = 'valid';
                } else if (result.status === 'used') {
                    this.sheet = 'used';
                    this.feedback('warning');
                    this.$store.toasts.add('Tiket sudah digunakan', 'warning');
                } else {
                    this.sheet = 'invalid';
                    this.feedback('danger');
                    this.$store.toasts.add('Tiket tidak ditemukan', 'danger');
                }
            } catch (error) {
                this.sheet = 'offline';
                this.$store.toasts.add('Koneksi internet terputus', 'warning');
            } finally {
                this.busy = false;
            }
        },

        async confirmCheckIn() {
            if (this.confirming || ! this.result?.code) {
                return;
            }

            this.confirming = true;

            try {
                const result = await this.post(checkInUrl, { code: this.result.code, ...this.scannerDevicePayload() });
                this.result = result;

                if (result.status === 'success') {
                    this.sheet = 'success';
                    this.feedback('success');
                    this.$store.toasts.add('Check-in berhasil', 'success');
                    this.successResetTimer = window.setTimeout(() => {
                        if (this.sheet === 'success') {
                            this.closeSheet();
                        }
                    }, SUCCESS_SHEET_MS);
                } else if (result.status === 'used') {
                    this.sheet = 'used';
                    this.feedback('warning');
                    this.$store.toasts.add('Tiket sudah digunakan', 'warning');
                } else {
                    this.sheet = 'invalid';
                    this.feedback('danger');
                    this.$store.toasts.add('Tiket tidak ditemukan', 'danger');
                }
            } catch (error) {
                this.sheet = 'offline';
                this.$store.toasts.add('Koneksi internet terputus', 'warning');
            } finally {
                this.confirming = false;
            }
        },

        closeSheet() {
            if (this.successResetTimer) {
                window.clearTimeout(this.successResetTimer);
                this.successResetTimer = null;
            }

            // Remember the code just handled; only this exact code is suppressed.
            this.lastHandledCode = String(this.result?.code ?? '');
            this.rescanAfter = Date.now() + RESCAN_COOLDOWN_MS;

            this.sheet = null;
            this.busy = false;
            this.confirming = false;
            this.result = {};

            // "Scan Berikutnya": decoding resumes without restarting the camera.
            this.startDecoder();

            this.refocus();
        },

        /**
         * Sound + haptics for the operator. Both APIs are optional and are skipped
         * silently when the browser does not support them.
         */
        feedback(type) {
            this.vibrate(type);

            try {
                const Context = window.AudioContext || window.webkitAudioContext;

                if (! Context) {
                    return;
                }

                this.audioContext = this.audioContext ?? new Context();

                if (this.audioContext.state === 'suspended') {
                    Promise.resolve(this.audioContext.resume?.()).catch(() => {});
                }

                const tones = { success: 1046, warning: 660, danger: 311 };
                const oscillator = this.audioContext.createOscillator();
                const gain = this.audioContext.createGain();

                oscillator.type = type === 'success' ? 'sine' : 'square';
                oscillator.frequency.value = tones[type] ?? tones.warning;
                gain.gain.value = 0.05;

                oscillator.connect(gain);
                gain.connect(this.audioContext.destination);
                oscillator.start();
                oscillator.stop(this.audioContext.currentTime + (type === 'success' ? 0.18 : 0.3));
            } catch (error) {
                // Audio feedback is a nice-to-have.
            }
        },

        vibrate(type) {
            if (typeof navigator.vibrate !== 'function') {
                return;
            }

            try {
                navigator.vibrate(type === 'success' ? 70 : [50, 60, 50]);
            } catch (error) {
                // Haptics are a nice-to-have.
            }
        },

        refocus() {
            this.$nextTick(() => {
                if (this.mode === 'device') {
                    this.$refs.deviceInput?.focus();
                } else if (this.lastFocused && document.contains(this.lastFocused)) {
                    this.lastFocused.focus();
                }
            });
        },

        async post(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify(payload),
            });

            if (! response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            return response.json();
        },
    }));

});

renderIcons();
Alpine.start();
