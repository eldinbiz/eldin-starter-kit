---
trigger: model_decision
description: Triggers when creating or altering frontend layouts, HTML views, and UI components. Enforces Alpine.js v3 client-side reactivity via CDN, strict attribute structure, and seamless interoperability with Laravel's $wire framework hooks.
---

# Alpine.js Reactivity Rule (Zero-Build Runtime)

This rule strictly governs all lightweight client-side interactions and reactivity, ensuring the AI agent adheres to the declarative, inline paradigm of Alpine.js v3 with zero compilation overhead.

## 1. Zero-Build Architecture
- **No NPM Bundling:** Do not install alpinejs via npm or import it into an asset bundler unless explicitly instructed.
- **The Defer Script Tag:** Ensure layout files load Alpine via its official CDN with the mandatory `defer` attribute inside the `<head>` tag so it plays nicely with other scripts:
  ```html
  <script defer src="[https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js](https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js)"></script>
  ```

## 2. Structural Paradigms & State Location
- **Inline Attributes for Small States:** For basic UI states (e.g., toggling a dropdown, switching tabs, closing a modal), keep the state directly inside the HTML markup:
  ```html
  <div x-data="{ open: false }">
      <button @click="open = ! open">Toggle</button>
      <div x-show="open" @click.outside="open = false" x-cloak>Content</div>
  </div>
  ```
- **`Alpine.data()` for Complex UI:** If a component requires extensive methods, loop filtering, or computed getters, do not write large blocks of raw JavaScript inline. Extract it using a global `Alpine.data()` declaration.
- **Always use `x-cloak`:** Apply `x-cloak` to components using `x-show` or `x-if` to avoid layout flashes before Alpine initializes. Ensure the global CSS snippet is added (`[x-cloak] { display: none !important; }`).

## 3. NativePHP / Livewire Interoperability
When Alpine components need to sync with backend data via Laravel Livewire or trigger desktop hooks:
- Use **`@entangle`** to bind Alpine state seamlessly with backend Livewire properties:
  ```html
  <div x-data="{ open: @entangle('showModal') }">
  ```
- Use the **`$wire`** magic helper to call backend Controller/Livewire code directly inside your interactive client-side events:
  ```html
  <button @click="$wire.saveData().then(() => alert('Saved!'))">Save</button>
  ```

## 4. Forbidden Anti-Patterns for AI Generation
- **No Vanilla QuerySelectors:** Do not generate scripts using `document.getElementById` or `querySelector` inside an Alpine context. Use native Alpine magic utilities like `$refs`, `$el`, `$dispatch`, or `$root`.
- **Avoid Over-nesting States:** Ensure inner structures understand outer `x-data` scoping rules without duplicating variables unnecessarily.