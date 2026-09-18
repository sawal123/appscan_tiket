<section class="camera-mode" id="cameraMode" aria-label="Pemindai kamera" data-testid="camera-mode-panel" x-show="mode === 'camera'">
    <div class="camera-viewport" id="cameraViewport" data-testid="camera-viewport" x-bind:class="{ 'is-active': cameraActive, 'is-detected': detected }">
        <video id="cameraVideo" playsinline muted data-testid="camera-video" x-ref="cameraVideo"></video>

        <div class="camera-placeholder" id="cameraPlaceholder" data-testid="camera-placeholder" x-show="! cameraActive">
            <span class="camera-placeholder__icon"><i data-lucide="camera"></i></span>
            <strong x-text="cameraMessageTitle">Kamera belum aktif</strong>
            <span x-text="cameraMessageText">Izinkan akses untuk mulai memindai</span>
        </div>

        <div class="scan-overlay" aria-hidden="true">
            <span class="scan-corner scan-corner--tl"></span><span class="scan-corner scan-corner--tr"></span>
            <span class="scan-corner scan-corner--bl"></span><span class="scan-corner scan-corner--br"></span>
            <span class="scan-line"></span>
        </div>

        <div class="camera-controls">
            <button class="camera-control" type="button" id="flashButton" aria-label="Nyalakan lampu" data-testid="flashlight-button" x-show="torchSupported" x-bind:class="{ 'is-active': flashOn }" x-on:click="toggleFlash()"><i data-lucide="flashlight"></i></button>
            <button class="camera-control" type="button" id="switchCameraButton" aria-label="Ganti kamera" data-testid="switch-camera-button" x-on:click="switchCamera()"><i data-lucide="switch-camera"></i></button>
        </div>

        <button class="button button--camera" type="button" id="activateCamera" data-testid="activate-camera-button" x-show="! cameraActive" x-bind:disabled="cameraLoading" x-on:click="startCamera()">
            <span class="spinner" x-show="cameraLoading" aria-hidden="true"></span>
            <span x-show="! cameraLoading" x-cloak><i data-lucide="camera"></i></span>
            <span x-text="cameraLoading ? 'Mengaktifkan...' : 'Aktifkan Kamera'">Aktifkan Kamera</span>
        </button>
    </div>

    <div class="scan-instruction" data-testid="camera-instruction">
        <strong>Arahkan kamera ke QR tiket</strong>
        <span x-text="decoderStatus">QR akan terbaca otomatis</span>
    </div>
</section>
