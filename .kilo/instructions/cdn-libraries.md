# Framework-Agnostic Third-Party Libraries Rule (CDN Only)

This rule strictly governs the addition of external JavaScript libraries (such as calendars, datepickers, alert overlays, charts, etc.), forcing a lightweight, modular, zero-build paradigm.

## 1. Zero Build & CDN-Only Architecture
- **No NPM Installations:** Do not run `npm install` or add dependencies to `package.json` for client-side UI libraries.
- **Direct CDN Integration:** Always load the necessary assets via official, trusted CDNs (e.g., jsDelivr, unpkg) using standard `<script>` and `<link>` tags directly inside your HTML/Blade layouts.

## 2. Strict Framework Agnosticism & Alpine.js Exception
- **Vanilla JS First:** Any library chosen MUST be entirely framework-agnostic (Pure/Vanilla JS).
- **Prohibited Dependencies:** Absolutely do not inject **jQuery**, or wrappers dependent on heavy frameworks (React, Vue, Angular) unless explicitly requested.
- **EXCEPTIONS (Allowed Alpine Ecosystem):** Official Alpine.js core plugins (e.g., Mask, Intersect, Collapse, Focus) or community libraries explicitly built to extend Alpine.js are fully allowed and encouraged.
- **Approved Equivalents Examples:**
  - Instead of jQuery Datepicker → Use **Flatpickr** (Vanilla) or an **Alpine-based picker**.
  - Instead of jQuery Accordion/Modal → Use **Alpine.js built-in logic** or **Alpine Collapse plugin**.
  - Instead of legacy SweetAlert → Use **SweetAlert2** (Vanilla/Agnostic).

## 3. Alpine.js Component Initialization Lifecycle
When initializing framework-agnostic plugins within the DOM, leverage Alpine.js lifecycles to maintain encapsulation. Initialize the agnostic plugin inside the `x-init` attribute of a wrapper element and bind values cleanly:
```html
<div x-data="{ date: '' }" x-init="flatpickr($refs.picker, { onChange: (selectedDates, dateStr) => date = dateStr })">
    <input type="text" x-ref="picker" x-model="date" class="border p-2">
</div>
```

## 4. Asset Ordering
Ensure that third-party JavaScript scripts are included *before* the main Alpine.js defer script or handled carefully within execution wrappers so methods are globally available when the DOM initializes.
