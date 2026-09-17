<div class="toast-region" id="toastRegion" aria-live="polite" aria-atomic="true" data-testid="toast-region">
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div
            class="toast"
            role="status"
            x-init="$nextTick(() => window.renderIcons())"
            x-bind:class="[`toast--${toast.type}`, { 'is-leaving': toast.leaving }]"
            x-bind:data-testid="`toast-${toast.id}`"
        >
            <i x-bind:data-lucide="toast.icon"></i>
            <span x-text="toast.message"></span>
        </div>
    </template>
</div>
