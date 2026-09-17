# Coding Preferences

- Works in Laravel with Livewire, Alpine.js, and Vue.js; expects the assistant to be competent in that stack. Confidence: 0.9
- Uses Livewire class + separate Blade view; never single-file Livewire components. Confidence: 0.95
- Alpine.js is only for local UI state; no custom JavaScript for CRUD operations. Confidence: 0.9
- Follows the existing admin/dashboard UI style exactly (spacing, cards, typography, buttons, borders, modals, responsive behavior); do not redesign. Confidence: 0.9
- Do not install new UI libraries. Confidence: 0.85
- Prefers additive migrations; never modifies previously working auth/role migrations. Confidence: 0.9
- Avoids over-engineering: no extra packages, abstractions, repositories, services, or components unless the scope requires them. Confidence: 0.9
- Use backed enums (e.g. status enums) when consistent with existing project style. Confidence: 0.7
- Prioritizes data safety over features — e.g. avoid hard-deleting records that have relations rather than implementing full delete. Confidence: 0.7
- Prefers reusable UI built as classless (anonymous) Blade components under `resources/views/components/...` (e.g. `admin/ui/*`, `admin/form/*`) rather than class-based components. Confidence: 0.75
- Expects Blade components to expose flexible props, keep their slots, and forward extra attributes via `$attributes->merge()` so `wire:model`/`wire:click`, `data-testid`, `aria-*`, and CSS classes still pass through; must stay compatible with Livewire and Alpine. Confidence: 0.75
