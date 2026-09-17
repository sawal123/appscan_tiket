(function () {
  "use strict";

  const STORAGE = {
    theme: "ticket-scanner-theme",
    tickets: "ticket-scanner-verified"
  };

  const demoTickets = [
    { code: "VIP-A8F2-09231", category: "VIP", time: "19:02:15", gate: "Gate A", scanner: "Scanner 02", date: "17 Sep 2026" },
    { code: "REG-B7K4-01842", category: "Regular", time: "18:56:08", gate: "Gate A", scanner: "Scanner 02", date: "17 Sep 2026" },
    { code: "VVIP-C2D9-00417", category: "VVIP", time: "18:51:44", gate: "Gate A", scanner: "Scanner 01", date: "17 Sep 2026" },
    { code: "REG-M6P1-01084", category: "Regular", time: "18:48:21", gate: "Gate B", scanner: "Scanner 03", date: "17 Sep 2026" }
  ];

  const ticketCatalog = {
    "VIP-001": { code: "VIP-001", category: "VIP", event: "Music Fest Medan 2026", state: "valid" },
    "REG-001": { code: "REG-001", category: "Regular", event: "Music Fest Medan 2026", state: "valid" },
    "VIP-USED-001": { code: "VIP-USED-001", category: "VIP", event: "Music Fest Medan 2026", state: "used", time: "18:47", gate: "Gate B", scanner: "Scanner 01" }
  };

  const $ = (selector, scope = document) => scope.querySelector(selector);
  const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));
  const wait = (duration) => new Promise((resolve) => window.setTimeout(resolve, duration));

  function refreshIcons() {
    if (window.lucide) window.lucide.createIcons({ attrs: { "aria-hidden": "true" } });
  }

  function getTheme() {
    return localStorage.getItem(STORAGE.theme) || (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");
  }

  function applyTheme(theme) {
    document.documentElement.dataset.theme = theme;
    localStorage.setItem(STORAGE.theme, theme);
    $$('[data-theme-toggle]').forEach((button) => {
      const isDark = theme === "dark";
      button.innerHTML = `<i data-lucide="${isDark ? "sun" : "moon"}"></i>`;
      button.setAttribute("aria-label", isDark ? "Gunakan tema terang" : "Gunakan tema gelap");
    });
    refreshIcons();
  }

  function initTheme() {
    applyTheme(getTheme());
    $$('[data-theme-toggle]').forEach((button) => button.addEventListener("click", () => {
      applyTheme(document.documentElement.dataset.theme === "dark" ? "light" : "dark");
    }));
  }

  function showToast(message, type = "success") {
    const region = $("#toastRegion");
    if (!region) return;
    const icon = type === "success" ? "circle-check" : type === "warning" ? "triangle-alert" : "circle-x";
    const toast = document.createElement("div");
    toast.className = `toast toast--${type}`;
    toast.setAttribute("role", "status");
    toast.setAttribute("data-testid", `toast-${Date.now()}`);
    toast.innerHTML = `<i data-lucide="${icon}"></i><span>${message}</span>`;
    region.appendChild(toast);
    refreshIcons();
    window.setTimeout(() => {
      toast.classList.add("is-leaving");
      window.setTimeout(() => toast.remove(), 220);
    }, 3000);
  }

  function setButtonLoading(button, label) {
    button.disabled = true;
    button.dataset.originalContent = button.innerHTML;
    button.innerHTML = `<span class="spinner" aria-hidden="true"></span><span>${label}</span>`;
  }

  function restoreButton(button) {
    button.disabled = false;
    if (button.dataset.originalContent) button.innerHTML = button.dataset.originalContent;
    refreshIcons();
  }

  function initLogin() {
    const form = $("#loginForm");
    if (!form) return;
    const identity = $("#identity");
    const password = $("#password");
    const loginError = $("#loginError");
    const button = $("#loginButton");

    $("#passwordToggle").addEventListener("click", (event) => {
      const visible = password.type === "text";
      password.type = visible ? "password" : "text";
      event.currentTarget.innerHTML = `<i data-lucide="${visible ? "eye" : "eye-off"}"></i>`;
      event.currentTarget.setAttribute("aria-label", visible ? "Tampilkan password" : "Sembunyikan password");
      refreshIcons();
    });

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      loginError.hidden = true;
      $$(".form-field", form).forEach((field) => field.classList.remove("has-error"));
      $("#identityError").textContent = "";
      $("#passwordError").textContent = "";

      let invalid = false;
      if (!identity.value.trim()) {
        identity.closest(".form-field").classList.add("has-error");
        $("#identityError").textContent = "Email atau username wajib diisi.";
        invalid = true;
      }
      if (!password.value) {
        password.closest(".form-field").classList.add("has-error");
        $("#passwordError").textContent = "Password wajib diisi.";
        invalid = true;
      }
      if (invalid) return;

      setButtonLoading(button, "Memproses...");
      await wait(650);
      if (password.value.toLowerCase() === "salah") {
        restoreButton(button);
        loginError.hidden = false;
        loginError.focus?.();
        return;
      }
      showToast("Login berhasil. Membuka scanner...", "success");
      await wait(420);
      window.location.href = "scanner.html";
    });
  }

  function getVerifiedTickets() {
    const saved = localStorage.getItem(STORAGE.tickets);
    if (saved === null) {
      localStorage.setItem(STORAGE.tickets, JSON.stringify(demoTickets));
      return [...demoTickets];
    }
    try { return JSON.parse(saved); } catch (_) { return [...demoTickets]; }
  }

  function saveVerifiedTicket(ticket) {
    const tickets = getVerifiedTickets();
    const now = new Date();
    const record = {
      code: ticket.code,
      category: ticket.category,
      time: now.toLocaleTimeString("id-ID", { hour12: false }),
      gate: "Gate A",
      scanner: "Scanner 02",
      date: "17 Sep 2026"
    };
    const withoutDuplicate = tickets.filter((item) => item.code !== ticket.code);
    localStorage.setItem(STORAGE.tickets, JSON.stringify([record, ...withoutDuplicate]));
    return record;
  }

  function initScanner() {
    const cameraMode = $("#cameraMode");
    if (!cameraMode) return;
    const deviceMode = $("#deviceMode");
    const deviceInput = $("#deviceInput");
    const segmented = $(".segmented-control");
    const resultBackdrop = $("#resultBackdrop");
    const resultContent = $("#resultContent");
    const viewport = $("#cameraViewport");
    const video = $("#cameraVideo");
    const placeholder = $("#cameraPlaceholder");
    const activateButton = $("#activateCamera");
    const connectionButton = $("#connectionToggle");
    const connectionLabel = $("#connectionLabel");
    let stream = null;
    let facingMode = "environment";
    let isOnline = true;
    let isProcessing = false;
    let isConfirming = false;
    let activeTicket = null;
    let activeMode = "camera";

    const templates = {
      validating: (code) => `
        <div class="result-status result-status--valid" data-testid="validating-state">
          <span class="result-status__icon"><span class="spinner"></span></span>
          <p class="result-status__kicker">Membaca QR</p>
          <h2 id="resultTitle" data-testid="validating-title">Memeriksa tiket...</h2>
          <p class="result-status__description" data-testid="validating-code">${code}</p>
        </div>`,
      valid: (ticket) => `
        <div class="result-status result-status--valid" data-testid="valid-ticket-state">
          <span class="result-status__icon"><i data-lucide="badge-check"></i></span>
          <p class="result-status__kicker">Tiket Ditemukan</p>
          <h2 id="resultTitle" data-testid="valid-ticket-title">Tiket Valid</h2>
          <p class="result-status__description" data-testid="valid-ticket-description">Tiket aktif dan belum digunakan.</p>
        </div>
        <dl class="result-details" data-testid="valid-ticket-details">
          <div><dt>Kategori</dt><dd data-testid="valid-ticket-category">${ticket.category}</dd></div>
          <div><dt>Status</dt><dd data-testid="valid-ticket-status">Belum Terverifikasi</dd></div>
          <div class="detail-wide"><dt>Event</dt><dd data-testid="valid-ticket-event">${ticket.event}</dd></div>
          <div class="detail-wide"><dt>QR</dt><dd data-testid="valid-ticket-code">${ticket.code}</dd></div>
        </dl>
        <div class="result-actions">
          <button class="button button--primary button--large" type="button" id="confirmTicket" data-testid="confirm-ticket-button"><i data-lucide="check"></i><span>Konfirmasi Masuk</span></button>
          <button class="button button--secondary button--large" type="button" data-close-result data-testid="cancel-ticket-button">Batalkan</button>
        </div>`,
      success: (ticket, record) => `
        <div class="result-status result-status--success" data-testid="success-ticket-state">
          <span class="result-status__icon"><i data-lucide="circle-check-big"></i></span>
          <p class="result-status__kicker">Check-in Selesai</p>
          <h2 id="resultTitle" data-testid="success-ticket-title">Tiket Berhasil Diverifikasi</h2>
          <p class="result-status__description" data-testid="success-ticket-description">Gate siap menerima pengunjung berikutnya.</p>
        </div>
        <dl class="result-details" data-testid="success-ticket-details">
          <div><dt>Kategori</dt><dd data-testid="success-ticket-category">${ticket.category}</dd></div>
          <div><dt>Waktu</dt><dd data-testid="success-ticket-time">${record.time}</dd></div>
          <div><dt>Gate</dt><dd data-testid="success-ticket-gate">Gate A</dd></div>
          <div><dt>Scanner</dt><dd data-testid="success-ticket-scanner">Scanner 02</dd></div>
        </dl>
        <div class="result-actions"><button class="button button--primary button--large" type="button" data-close-result data-testid="scan-next-success-button"><i data-lucide="scan-line"></i>Scan Tiket Berikutnya</button></div>`,
      used: (ticket) => `
        <div class="result-status result-status--warning" data-testid="used-ticket-state">
          <span class="result-status__icon"><i data-lucide="history"></i></span>
          <p class="result-status__kicker">Perlu Perhatian</p>
          <h2 id="resultTitle" data-testid="used-ticket-title">Tiket Sudah Digunakan</h2>
          <p class="result-status__description" data-testid="used-ticket-description">Tiket ini telah tercatat masuk sebelumnya.</p>
        </div>
        <dl class="result-details" data-testid="used-ticket-details">
          <div><dt>Kategori</dt><dd data-testid="used-ticket-category">${ticket.category}</dd></div>
          <div><dt>Diverifikasi</dt><dd data-testid="used-ticket-time">17 Sep 2026 · ${ticket.time}</dd></div>
          <div><dt>Gate</dt><dd data-testid="used-ticket-gate">${ticket.gate}</dd></div>
          <div><dt>Scanner</dt><dd data-testid="used-ticket-scanner">${ticket.scanner}</dd></div>
        </dl>
        <div class="result-actions"><button class="button button--primary button--large" type="button" data-close-result data-testid="scan-next-used-button"><i data-lucide="scan-line"></i>Scan Tiket Berikutnya</button></div>`,
      invalid: (code) => `
        <div class="result-status result-status--danger" data-testid="invalid-ticket-state">
          <span class="result-status__icon"><i data-lucide="circle-x"></i></span>
          <p class="result-status__kicker">QR Tidak Valid</p>
          <h2 id="resultTitle" data-testid="invalid-ticket-title">Tiket Tidak Ditemukan</h2>
          <p class="result-status__description" data-testid="invalid-ticket-description">QR ini tidak terdaftar pada event aktif.</p>
        </div>
        <dl class="result-details"><div class="detail-wide"><dt>QR</dt><dd data-testid="invalid-ticket-code">${code}</dd></div></dl>
        <div class="result-actions">
          <button class="button button--primary button--large" type="button" data-close-result data-testid="rescan-invalid-button"><i data-lucide="refresh-cw"></i>Scan Ulang</button>
          <button class="button button--secondary button--large" type="button" data-open-manual data-testid="invalid-manual-input-button"><i data-lucide="keyboard"></i>Masukkan QR Manual</button>
        </div>`,
      offline: () => `
        <div class="result-status result-status--offline" data-testid="offline-state">
          <span class="result-status__icon"><i data-lucide="wifi-off"></i></span>
          <p class="result-status__kicker">Sistem Tidak Terhubung</p>
          <h2 id="resultTitle" data-testid="offline-title">Koneksi Terputus</h2>
          <p class="result-status__description" data-testid="offline-description">Periksa koneksi internet sebelum melanjutkan proses check-in.</p>
        </div>
        <div class="result-actions"><button class="button button--primary button--large" type="button" id="retryConnection" data-testid="retry-connection-button"><i data-lucide="refresh-cw"></i>Coba Lagi</button></div>`
    };

    function openResult(content) {
      resultContent.innerHTML = content;
      resultBackdrop.hidden = false;
      document.body.style.overflow = "hidden";
      refreshIcons();
      window.setTimeout(() => $("button", resultContent)?.focus(), 80);
    }

    function closeResult() {
      resultBackdrop.hidden = true;
      document.body.style.overflow = "";
      isProcessing = false;
      isConfirming = false;
      activeTicket = null;
      if (activeMode === "device") window.setTimeout(() => deviceInput.focus(), 60);
    }

    async function processCode(rawCode) {
      const code = rawCode.trim().toUpperCase();
      if (!code || isProcessing) return;
      isProcessing = true;
      viewport.classList.add("is-detected");
      window.setTimeout(() => viewport.classList.remove("is-detected"), 400);

      if (!isOnline) {
        openResult(templates.offline());
        showToast("Koneksi internet terputus", "warning");
        return;
      }

      openResult(templates.validating(code));
      await wait(520);
      activeTicket = ticketCatalog[code] || { code, state: "invalid" };
      if (activeTicket.state === "valid") {
        openResult(templates.valid(activeTicket));
      } else if (activeTicket.state === "used") {
        openResult(templates.used(activeTicket));
        showToast("Tiket sudah digunakan", "warning");
      } else {
        openResult(templates.invalid(code));
        showToast("Tiket tidak ditemukan", "danger");
      }
    }

    function setOnline(nextOnline) {
      isOnline = nextOnline;
      connectionButton.classList.toggle("is-offline", !isOnline);
      connectionLabel.textContent = isOnline ? "Online" : "Offline";
      connectionButton.setAttribute("aria-label", isOnline ? "Status online. Klik untuk simulasi offline" : "Status offline. Klik untuk kembali online");
      if (!isOnline) {
        openResult(templates.offline());
        showToast("Koneksi internet terputus", "warning");
      } else {
        if (!resultBackdrop.hidden) closeResult();
        showToast("Koneksi kembali online", "success");
      }
    }

    $$(".segment").forEach((tab) => tab.addEventListener("click", () => {
      activeMode = tab.dataset.mode;
      $$(".segment").forEach((item) => {
        const active = item === tab;
        item.classList.toggle("is-active", active);
        item.setAttribute("aria-selected", String(active));
      });
      const deviceActive = activeMode === "device";
      segmented.classList.toggle("is-device", deviceActive);
      cameraMode.hidden = deviceActive;
      deviceMode.hidden = !deviceActive;
      if (deviceActive) window.setTimeout(() => deviceInput.focus(), 100);
    }));

    async function startCamera() {
      if (!navigator.mediaDevices?.getUserMedia) {
        showToast("Kamera membutuhkan browser dengan akses aman", "warning");
        placeholder.querySelector("strong").textContent = "Kamera tidak tersedia";
        placeholder.querySelector("span:last-child").textContent = "Gunakan tombol demo atau Scanner Device";
        return;
      }
      activateButton.disabled = true;
      activateButton.innerHTML = `<span class="spinner"></span><span>Mengaktifkan...</span>`;
      try {
        if (stream) stream.getTracks().forEach((track) => track.stop());
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode }, audio: false });
        video.srcObject = stream;
        await video.play();
        viewport.classList.add("is-active");
        placeholder.hidden = true;
        activateButton.hidden = true;
        showToast("Kamera aktif dan siap memindai", "success");
      } catch (_) {
        activateButton.disabled = false;
        activateButton.innerHTML = `<i data-lucide="camera"></i>Aktifkan Kamera`;
        placeholder.querySelector("strong").textContent = "Akses kamera ditolak";
        placeholder.querySelector("span:last-child").textContent = "Izinkan kamera atau gunakan mode perangkat";
        showToast("Akses kamera belum diberikan", "danger");
        refreshIcons();
      }
    }

    activateButton.addEventListener("click", startCamera);
    $("#switchCameraButton").addEventListener("click", async () => {
      facingMode = facingMode === "environment" ? "user" : "environment";
      if (stream) await startCamera(); else showToast("Aktifkan kamera terlebih dahulu", "warning");
    });
    $("#flashButton").addEventListener("click", (event) => {
      event.currentTarget.classList.toggle("is-active");
      showToast(event.currentTarget.classList.contains("is-active") ? "Lampu simulasi dinyalakan" : "Lampu simulasi dimatikan", "success");
    });
    connectionButton.addEventListener("click", () => setOnline(!isOnline));
    $$("[data-demo-code]").forEach((button) => button.addEventListener("click", () => processCode(button.dataset.demoCode)));
    deviceInput.addEventListener("keydown", (event) => {
      if (event.key === "Enter") {
        event.preventDefault();
        const code = deviceInput.value;
        deviceInput.value = "";
        processCode(code);
      }
    });

    resultBackdrop.addEventListener("click", async (event) => {
      if (event.target === resultBackdrop || event.target.closest("[data-close-result]")) closeResult();
      const confirm = event.target.closest("#confirmTicket");
      if (confirm && activeTicket && !confirm.disabled && !isConfirming) {
        isConfirming = true;
        setButtonLoading(confirm, "Memverifikasi...");
        await wait(680);
        const record = saveVerifiedTicket(activeTicket);
        resultContent.innerHTML = templates.success(activeTicket, record);
        refreshIcons();
        showToast("Tiket berhasil diverifikasi", "success");
      }
      if (event.target.closest("[data-open-manual]")) {
        closeResult();
        openManual();
      }
      if (event.target.closest("#retryConnection")) setOnline(true);
    });

    const manualModal = $("#manualModal");
    const manualInput = $("#manualCode");
    const lastFocused = { element: null };
    function openManual() {
      lastFocused.element = document.activeElement;
      manualModal.hidden = false;
      document.body.style.overflow = "hidden";
      window.setTimeout(() => manualInput.focus(), 80);
    }
    function closeManual() {
      manualModal.hidden = true;
      document.body.style.overflow = "";
      manualInput.value = "";
      $("#manualError").textContent = "";
      (activeMode === "device" ? deviceInput : lastFocused.element)?.focus?.();
    }
    $("#manualOpen").addEventListener("click", openManual);
    $("#manualClose").addEventListener("click", closeManual);
    manualModal.addEventListener("click", (event) => { if (event.target === manualModal) closeManual(); });
    $("#manualForm").addEventListener("submit", (event) => {
      event.preventDefault();
      if (!manualInput.value.trim()) {
        $("#manualError").textContent = "Kode QR wajib diisi.";
        manualInput.closest(".form-field").classList.add("has-error");
        return;
      }
      const code = manualInput.value;
      closeManual();
      processCode(code);
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        if (!manualModal.hidden) closeManual();
        else if (!resultBackdrop.hidden) closeResult();
      }
    });
  }

  function initVerified() {
    const list = $("#verifiedList");
    if (!list) return;
    const emptyState = $("#emptyState");
    const count = $("#verifiedCount");
    const search = $("#ticketSearch");
    let activeFilter = "Semua";

    function render() {
      const allTickets = getVerifiedTickets();
      const term = search.value.trim().toLowerCase();
      const filtered = allTickets.filter((ticket) => {
        const filterMatch = activeFilter === "Semua" || ticket.category === activeFilter;
        const searchMatch = ticket.code.toLowerCase().includes(term);
        return filterMatch && searchMatch;
      });
      count.textContent = `${allTickets.length} tiket telah masuk`;
      list.innerHTML = filtered.map((ticket, index) => `
        <article class="ticket-row" data-testid="verified-ticket-row-${index}" style="animation-delay:${Math.min(index * 35, 210)}ms">
          <div class="ticket-identity">
            <span class="ticket-check" aria-hidden="true"><i data-lucide="check"></i></span>
            <span class="ticket-code"><span class="ticket-badge" data-testid="ticket-category-${index}">${ticket.category}</span><strong data-testid="ticket-code-${index}">${ticket.code}</strong></span>
          </div>
          <span class="ticket-cell" data-testid="ticket-time-${index}">${ticket.time}</span>
          <span class="ticket-cell" data-testid="ticket-gate-${index}">${ticket.gate}</span>
          <span class="ticket-status" data-testid="ticket-status-${index}">Masuk</span>
        </article>`).join("");
      const noDataAtAll = allTickets.length === 0;
      emptyState.hidden = !noDataAtAll;
      list.hidden = noDataAtAll;
      if (!noDataAtAll && filtered.length === 0) {
        list.innerHTML = `<div class="empty-state" data-testid="no-search-results"><span class="empty-state__icon"><i data-lucide="search-x"></i></span><h2>Tiket tidak ditemukan</h2><p>Coba kata kunci atau kategori lain.</p></div>`;
      }
      refreshIcons();
    }

    search.addEventListener("input", render);
    $$(".filter-chip").forEach((chip) => chip.addEventListener("click", () => {
      activeFilter = chip.dataset.filter;
      $$(".filter-chip").forEach((item) => item.classList.toggle("is-active", item === chip));
      render();
    }));
    $("#clearTickets").addEventListener("click", () => {
      localStorage.setItem(STORAGE.tickets, "[]");
      render();
      showToast("Daftar demo dikosongkan", "warning");
    });
    $("#restoreTickets").addEventListener("click", () => {
      localStorage.setItem(STORAGE.tickets, JSON.stringify(demoTickets));
      render();
      showToast("Data demo berhasil dipulihkan", "success");
    });
    document.addEventListener("keydown", (event) => {
      if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === "k") {
        event.preventDefault();
        search.focus();
      }
    });
    render();
  }

  document.addEventListener("DOMContentLoaded", () => {
    initTheme();
    initLogin();
    initScanner();
    initVerified();
    refreshIcons();
  });
})();