---
trigger: model_decision
description: Triggers when creating or altering frontend layouts, HTML views, and UI components. Enforces Alpine.js v3 client-side reactivity (provided by Livewire v4 core), strict attribute structure, and seamless interoperability with Laravel's $wire framework hooks.
---

# Alpine.js Reactivity Rule (Livewire v4 Bundled Core)

This rule strictly governs all lightweight client-side interactions and reactivity, ensuring the AI agent adheres to the declarative, inline paradigm of Alpine.js v3 with zero compilation overhead.

## 1. Livewire v4 Bundled Alpine Core
- **No Standalone CDN Scripts in Layouts:** Livewire v4 natively bundles and initializes the Alpine.js v3 core runtime. Do NOT inject separate standalone Alpine CDN `<script defer src="...alpine.min.js">` tags in Blade layouts, as this creates duplicate runtime instances and breaks component state lifecycles.
- **Plugins:** Official Alpine plugins (e.g. `x-trap`, `x-mask`, `x-collapse`) are bundled or initialized cleanly via Livewire directives.

## 2. Structural Paradigms & State Location
- **Inline Attributes for Small States:** For basic UI states (e.g., toggling a dropdown, switching tabs, closing a modal), keep the state directly inside the HTML markup:
  ```html
  <div x-data="{ open: false }">
      <button @click="open = ! open">Toggle</button>
      <div x-show="open" @click.outside="open = false" x-cloak>Content</div>
  </div>
  ```
- **`Alpine.data()` for Complex UI:** If a component requires extensive methods, loop filtering, async API calls, or computed getters (anything beyond ~5 lines of state), do not write large blocks of raw JavaScript inline inside HTML attributes. Extract it using `Alpine.data()` wrapped in `document.addEventListener('alpine:init', ...)` and pass server data via Laravel's `@js()` helper:
  ```blade
  <div x-data="myComponent(@js($data))">...</div>

  <script>
      document.addEventListener('alpine:init', () => {
          Alpine.data('myComponent', (data) => ({
              items: data,
              async fetchData() { ... }
          }));
      });
  </script>
  ```
- **Always use `x-cloak`:** Apply `x-cloak` to components using `x-show` or `x-if` to avoid layout flashes before Alpine initializes. Ensure the global CSS snippet is present (`[x-cloak] { display: none !important; }`).

## 3. Livewire v4 Interoperability
When Alpine components need to sync with backend data via Laravel Livewire:
- Use **`$wire`** to bind Alpine state and trigger backend Livewire actions directly:
  ```html
  <button @click="$wire.save().then(() => alert('Saved!'))">Save</button>
  ```
- Use **`@entangle`** for two-way state binding between Alpine and Livewire:
  ```html
  <div x-data="{ open: @entangle('modalOpen') }">
  ```
- **Unconditional Modal DOM Preservation:** For modals and overlays containing form inputs or editor containers, keep the container structure unconditionally in the DOM using Alpine (`x-show="$wire.modalOpen"` and `x-cloak`) rather than Blade `@if ($modalOpen)`. This prevents DOM layout jumping and ensures backward compatibility with legacy HTTP assertions testing static HTML.

## 4. Forbidden Anti-Patterns for AI Generation
- **No Multi-Line Inline Logic in Attributes:** Never write complex async fetch requests or multi-line (>5 lines) JavaScript objects directly inside an HTML `x-data` attribute. Always extract them to a named `Alpine.data()` component or delegate to Livewire backend methods.
- **No Vanilla QuerySelectors:** Do not generate scripts using `document.getElementById` or `querySelector` inside an Alpine context. Use native Alpine magic utilities like `$refs`, `$el`, `$dispatch`, or `$root`.
- **Avoid Over-nesting States:** Ensure inner structures understand outer `x-data` scoping rules without duplicating variables unnecessarily.

## 5. Confirmation Modals & Type-to-Confirm
When building confirmation modals with type-to-confirm for destructive actions, consult the on-demand skill: [.agents/skills/alpine-modal/SKILL.md](file:///c:/Users/Public/Downloads/projects/scaffolding-app-laravel/.agents/skills/alpine-modal/SKILL.md).