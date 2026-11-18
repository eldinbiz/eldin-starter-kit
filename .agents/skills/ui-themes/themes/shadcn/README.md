# shadcn Sub-Theme Standard (`themes/shadcn`)

This directory defines the **shadcn** visual design specifications, component implementation patterns, and official upstream references for the multi-theme architecture.

---

## 1. Upstream Official Reference (100% Intact)

The official, unmodified `shadcn` upstream skill and rules are located in:
👉 **[`official/`](./official/SKILL.md)**

When designing or styling shadcn components, always consult the official upstream rules:
- **Styling Rules**: [`official/rules/styling.md`](./official/rules/styling.md) (Use `flex gap-*` instead of `space-y-*`, `size-*` shorthand, semantic zinc colors).
- **Composition Rules**: [`official/rules/composition.md`](./official/rules/composition.md) (Card composition, Modal/Dialog accessibility, table structure).
- **Forms & Inputs**: [`official/rules/forms.md`](./official/rules/forms.md) (Input accessibility, validation states).
- **Icons**: [`official/rules/icons.md`](./official/rules/icons.md) (Icon sizing and alignment).

---

## 2. Laravel Blade & Tailwind v4 Design Tokens

In this application, shadcn is implemented using native **Blade + Livewire v4 + Alpine.js** with zero React dependencies:

| Token Area | Value / Classes | Purpose |
| :--- | :--- | :--- |
| **Typography** | `font-sans` (`Inter`) | Crisp, modern grotesque sans-serif |
| **Borders** | `border border-zinc-200 dark:border-zinc-800` | Minimalist 1px high-precision outline |
| **Surfaces** | `bg-white dark:bg-zinc-950` | Clean high-contrast card and canvas surfaces |
| **Form Inputs** | `h-9 px-3 py-1 text-sm rounded-md` | High-density 36px input controls |
| **Focus Rings** | `focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300` | Radix-style sharp focus outline |
| **Primary Buttons** | `bg-zinc-900 text-zinc-50 hover:bg-zinc-900/90 dark:bg-zinc-50 dark:text-zinc-900` | High-contrast monochrome primary |
| **Overlays** | `bg-black/50 backdrop-blur-xs` | Refined subtle overlay backdrop |

---

## 3. Blade Few-Shot Implementation Templates

Reference implementations for all core UI components are available in [`examples/`](./examples/):

1. 📄 [`examples/button.blade.md`](./examples/button.blade.md) — Button variants (`default`, `secondary`, `outline`, `ghost`, `destructive`), sizes, loading spinner.
2. 📄 [`examples/input.blade.md`](./examples/input.blade.md) — High-density input, label, helper text, error handling.
3. 📄 [`examples/card.blade.md`](./examples/card.blade.md) — 1px zinc border card with title, description, content, and footer slots.
4. 📄 [`examples/modal.blade.md`](./examples/modal.blade.md) — Accessible modal with Alpine.js `x-trap.inert` and ESC listener.
5. 📄 [`examples/table.blade.md`](./examples/table.blade.md) — Compound data table (`table`, `header`, `row`, `cell`, `empty`).
6. 📄 [`examples/select.blade.md`](./examples/select.blade.md) — Dropdown select with array options and custom slots.
7. 📄 [`examples/alert.blade.md`](./examples/alert.blade.md) — Status alert banners (`info`, `success`, `warning`, `error`).
8. 📄 [`examples/badge.blade.md`](./examples/badge.blade.md) — Status badges (`default`, `secondary`, `outline`, `destructive`, `success`, `warning`).
9. 📄 [`examples/checkbox.blade.md`](./examples/checkbox.blade.md) — Radix-style checkbox with label, description helper, and validation error.
10. 📄 [`examples/dropdown.blade.md`](./examples/dropdown.blade.md) — Dropdown menu with Alpine transitions and alignment positioning.
11. 📄 [`examples/textarea.blade.md`](./examples/textarea.blade.md) — High-density multi-line text input with character styling and states.
12. 📄 [`examples/toggle.blade.md`](./examples/toggle.blade.md) — Accessible Alpine.js switch control (`role="switch"`).
