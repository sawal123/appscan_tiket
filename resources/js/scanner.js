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

const THEME_KEY = 'ticket-scanner-theme';

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

    Alpine.data('scannerApp', ({ validateUrl, checkInUrl }) => ({
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
        facingMode: 'environment',
        flashOn: false,
        stream: null,
        detector: null,
        detectTimer: null,

        manualOpen: false,
        manualCode: '',
        manualError: '',
        lastFocused: null,

        deviceCode: '',

        get usedAtLabel() {
            const date = this.result?.checked_in_date;
            const time = this.result?.checked_in_time;

            if (! date) {
                return '-';
            }

            return time ? `${date} · ${time}` : date;
        },

        init() {
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

                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode },
                    audio: false,
                });

                this.$refs.cameraVideo.srcObject = this.stream;
                await this.$refs.cameraVideo.play();

                this.cameraActive = true;
                this.startDetector();
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

        toggleFlash() {
            this.flashOn = ! this.flashOn;

            this.$store.toasts.add(
                this.flashOn ? 'Lampu simulasi dinyalakan' : 'Lampu simulasi dimatikan',
                'success',
            );
        },

        stopStream() {
            if (this.stream) {
                this.stream.getTracks().forEach((track) => track.stop());
                this.stream = null;
            }

            this.cameraActive = false;
            this.stopDetector();
        },

        startDetector() {
            this.stopDetector();

            if (typeof window.BarcodeDetector === 'undefined') {
                return;
            }

            try {
                this.detector = new window.BarcodeDetector({ formats: ['qr_code'] });
            } catch (error) {
                this.detector = null;

                return;
            }

            const scan = async () => {
                if (! this.cameraActive || ! this.detector) {
                    return;
                }

                if (this.sheet === null) {
                    try {
                        const codes = await this.detector.detect(this.$refs.cameraVideo);
                        const value = codes[0]?.rawValue;

                        if (value) {
                            this.submitCode(value);
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
                const result = await this.post(validateUrl, { code });
                this.result = result;

                if (result.status === 'valid') {
                    this.sheet = 'valid';
                } else if (result.status === 'used') {
                    this.sheet = 'used';
                    this.$store.toasts.add('Tiket sudah digunakan', 'warning');
                } else {
                    this.sheet = 'invalid';
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
                const result = await this.post(checkInUrl, { code: this.result.code });
                this.result = result;

                if (result.status === 'success') {
                    this.sheet = 'success';
                    this.$store.toasts.add('Tiket berhasil diverifikasi', 'success');
                } else if (result.status === 'used') {
                    this.sheet = 'used';
                    this.$store.toasts.add('Tiket sudah digunakan', 'warning');
                } else {
                    this.sheet = 'invalid';
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
            this.sheet = null;
            this.busy = false;
            this.confirming = false;
            this.result = {};

            this.refocus();
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

    Alpine.data('verifiedApp', (initialTickets = []) => ({
        tickets: initialTickets,
        search: '',
        filter: 'Semua',
        filters: ['Semua', 'VIP', 'Regular', 'VVIP'],

        get filtered() {
            const term = this.search.trim().toLowerCase();

            return this.tickets.filter((ticket) => {
                const matchesFilter = this.filter === 'Semua' || ticket.category === this.filter;
                const matchesSearch = String(ticket.code ?? '').toLowerCase().includes(term);

                return matchesFilter && matchesSearch;
            });
        },

        get countLabel() {
            return `${this.tickets.length} tiket telah masuk`;
        },
    }));
});

renderIcons();
Alpine.start();
