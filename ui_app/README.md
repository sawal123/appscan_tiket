# Laravel Slicing Source

Folder ini berisi hanya file source UI static dari Emergent yang relevan untuk dipindahkan/slicing ke project Laravel.

## Isi

- `login.html` -> source untuk `resources/views/auth/login.blade.php`
- `scanner.html` -> source untuk `resources/views/scanner/index.blade.php`
- `verified.html` -> source untuk `resources/views/scanner/verified.blade.php`
- `assets/css/styles.css` -> design system/style source; disarankan dipindahkan menjadi `resources/css/scanner.css`
- `assets/js/app.js` -> referensi behavior prototype saja. Jangan copy mentah sebagai logic production; pindahkan behavior yang diperlukan ke Alpine.js/JavaScript Laravel dan ganti dummy/localStorage dengan backend Laravel.

## Jangan dibawa ke Laravel

Folder scaffold Emergent seperti `.emergent/`, `backend/`, `frontend/`, `memory/`, `test_reports/`, dan `tests/` tidak diperlukan untuk slicing UI scanner.

## Catatan integrasi

HTML di folder ini masih static prototype. Saat slicing:

1. Pecah layout/header/navigation/modal/result menjadi Blade components.
2. Ganti link `.html` menjadi route Laravel.
3. Pertahankan CSS visual terlebih dahulu agar hasil slicing identik.
4. Adaptasi state scanner ke Alpine.js/JavaScript.
5. Hubungkan validasi dan check-in ke endpoint Laravel/session auth.
6. Jangan gunakan dummy ticket/localStorage sebagai sumber data production.
7. Escape nilai QR dari server/hasil scan; jangan masukkan raw input ke `innerHTML` tanpa sanitasi.

File asli di root repo tetap dipertahankan sebagai reference design.