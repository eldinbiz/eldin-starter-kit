# TailAdmin Sub-Theme Standard (`themes/tailadmin`)

This directory defines the **TailAdmin** visual design specifications, component implementation patterns, and styling tokens for the multi-theme architecture.

---

## 1. Design Tokens & Core Rules

In this application, TailAdmin provides a polished, modern enterprise dashboard aesthetic:

| Token Area | Value / Classes | Purpose |
| :--- | :--- | :--- |
| **Typography** | `font-outfit` (`Outfit`) | Clean geometric modern sans-serif |
| **Borders** | `border border-gray-200 dark:border-gray-800` | Soft gray container boundaries |
| **Card Containers** | `rounded-2xl bg-white shadow-theme-sm dark:bg-white/[0.03]` | Generous 16px corner curves |
| **Surfaces** | Light `bg-gray-50`, Dark `dark:bg-gray-900` | Balanced contrast layers |
| **Form Inputs** | `h-11 px-4 py-2.5 text-sm rounded-lg` | Standard 44px ergonomic touch inputs |
| **Focus Rings** | `focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10` | Soft brand blue glow ring |
| **Primary Buttons** | `bg-brand-500 hover:bg-brand-600 text-white rounded-lg shadow-theme-xs` | TailAdmin brand blue (`#465FFF`) |
| **Badges & Pills** | `rounded-full px-2.5 py-0.5 text-xs font-medium` | Rounded status pills |

---

## 2. Color Palette Reference

- **Brand Blue**: `brand-50` (`#EBF1FF`) $\rightarrow$ `brand-500` (`#465FFF`) $\rightarrow$ `brand-600` (`#3B4FE4`)
- **Success Green**: `success-50` (`#E8FDF0`) $\rightarrow$ `success-600` (`#039855`)
- **Error Red**: `error-50` (`#FEECEB`) $\rightarrow$ `error-600` (`#D92D20`)
- **Warning Amber**: `warning-50` (`#FEF6EE`) $\rightarrow$ `warning-600` (`#DC6803`)

---

## 3. Blade Few-Shot Implementation Templates

Reference implementations for all core UI components are available in [`examples/`](./examples/):

1. 📄 [`examples/button.blade.md`](./examples/button.blade.md) — Button variants (`primary`, `secondary`, `outline`, `ghost`, `destructive`), sizes, loading spinner.
2. 📄 [`examples/input.blade.md`](./examples/input.blade.md) — 44px input, label, helper text, error handling.
3. 📄 [`examples/card.blade.md`](./examples/card.blade.md) — `rounded-2xl` card with title, description, content, and footer slots.
4. 📄 [`examples/modal.blade.md`](./examples/modal.blade.md) — `rounded-2xl` modal with Alpine.js `x-trap.inert` and ESC listener.
5. 📄 [`examples/table.blade.md`](./examples/table.blade.md) — Compound data table with `rounded-2xl` card wrapper.
6. 📄 [`examples/select.blade.md`](./examples/select.blade.md) — 44px select with array options and custom slots.
7. 📄 [`examples/alert.blade.md`](./examples/alert.blade.md) — Soft brand colored status alert banners.
8. 📄 [`examples/badge.blade.md`](./examples/badge.blade.md) — Rounded-full status pill badges (`primary`, `secondary`, `success`, `warning`, `destructive`).
9. 📄 [`examples/checkbox.blade.md`](./examples/checkbox.blade.md) — TailAdmin checkbox with brand focus ring, label, description, and error state.
10. 📄 [`examples/dropdown.blade.md`](./examples/dropdown.blade.md) — Rounded-2xl dropdown menu card with smooth Alpine transitions.
11. 📄 [`examples/textarea.blade.md`](./examples/textarea.blade.md) — 44px-scale multi-line text input with brand focus ring and validation.
12. 📄 [`examples/toggle.blade.md`](./examples/toggle.blade.md) — Accessible Alpine.js switch control (`role="switch"`).
