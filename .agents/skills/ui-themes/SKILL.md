---
name: ui-themes
description: "Master multi-theme UI architecture skill for this application. Triggers on any UI styling, theme switching, component creation, or layout task. Manages <x-ui::*> abstract components, ThemeServiceProvider, layout proxies, config/ui.php, and php artisan ui:theme. Sub-themes: themes/shadcn/ (modern minimalist zinc) and themes/tailadmin/ (enterprise rounded-2xl). Always inspects config('ui.theme') to adhere to active theme."
license: MIT
metadata:
  author: eldinbiz
---

# Master Multi-Theme System Standard (`ui-themes`)

This skill defines the canonical architecture, component guidelines, and multi-theme orchestration standard for this application.

---

## 1. Core Architecture

The application uses an **Abstract, Pluggable Multi-Theme Architecture**:

```
Application Views / Livewire SFCs / Domain Modules (app-modules/)
                        │
                        ▼ (Consumes Abstract Namespace)
                 <x-ui::*> Components
                        │
                        ▼ (Dynamically Resolved by ThemeServiceProvider)
         resources/views/themes/{APP_UI_THEME}/
         ├── layouts/ (app.blade.php, auth.blade.php)
         └── components/ (card, button, input, modal, table, ...)
```

1. **Theme-Agnostic Views**: All views, Livewire components, and domain modules (`app-modules/<name>/resources/views/`) MUST consume abstract **`<x-ui::*>`** components (`<x-ui::card>`, `<x-ui::button>`, `<x-ui::input>`, `<x-ui::table>`, `<x-ui::modal>`, etc.). Never write raw theme-specific classes or hardcoded layout HTML directly in page templates.
2. **Dynamic Service Provider**: [`ThemeServiceProvider`](file:///c:/Users/Public/Downloads/projects/scaffolding-app-laravel/app/Providers/ThemeServiceProvider.php) reads `config('ui.theme')` (`APP_UI_THEME` in `.env`), registers `theme::components` under the `<x-ui::*>` alias, and points the `theme` view namespace to the active theme directory.
3. **Layout Proxies**: Standard layout paths `layouts.app` and `layouts.auth` dynamically extend `theme::layouts.app` and `theme::layouts.auth`.
4. **Theme Parity Rule**: When building a new atomic UI component, **always implement matching templates** across all configured themes so switching never breaks.

---

## 2. Active Theme Discovery Protocol

Whenever an AI agent is tasked with styling, creating, or modifying UI, follow this 3-step discovery protocol:

```
┌────────────────────────────────────────────────────────────────────────┐
│ 1. Active Theme Check:                                                 │
│    Inspect config('ui.theme') / APP_UI_THEME in .env                   │
│                                                                        │
│ 2. Sub-Theme Specification Resolution:                                 │
│    • If active theme is 'shadcn'   → Consult themes/shadcn/README.md   │
│    • If active theme is 'tailadmin'→ Consult themes/tailadmin/README.md│
│    • If active theme is '[other]'  → Consult themes/[other]/README.md  │
│                                                                        │
│ 3. Construction Rule:                                                  │
│    • In Page/Module Views: Consume abstract <x-ui::*> components       │
│    • In Atomic Components: Style according to active theme while       │
│      providing matching implementation in all themes for parity        │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Sub-Themes Index

Each sub-theme is completely self-contained in its own subdirectory with dedicated design guides and few-shot Blade component templates:

### 1. [`themes/shadcn/`](./themes/shadcn/README.md) — Minimalist Modern Zinc
- **Design Tokens**: High-density 36px inputs (`h-9`), crisp 1px zinc borders (`border-zinc-200 dark:border-zinc-800`), `rounded-md` / `rounded-lg` corners, Radix-style focus rings (`focus-visible:ring-1`).
- **Typography**: Inter (`font-sans`).
- **Upstream Reference**: Contains the 100% untouched official upstream `shadcn` skill in [`themes/shadcn/official/`](./themes/shadcn/official/SKILL.md).
- **Templates**: 12 complete few-shot Blade templates matching all core components in [`themes/shadcn/examples/`](./themes/shadcn/examples/).

### 2. [`themes/tailadmin/`](./themes/tailadmin/README.md) — Polished Enterprise Dashboard
- **Design Tokens**: Standard 44px inputs (`h-11`), `rounded-2xl` card curves, soft brand focus rings (`focus:ring-3 focus:ring-brand-500/10`), brand blue primary (`brand-500`: `#465FFF`).
- **Typography**: Outfit (`font-outfit`).
- **Templates**: 12 complete few-shot Blade templates matching all core components in [`themes/tailadmin/examples/`](./themes/tailadmin/examples/).

---

## 4. Master `<x-ui::*>` Component Catalog

All components are consumed via `<x-ui::*>` with uniform properties across all themes:

| Component | Usage Example | Key Props / Slots |
| :--- | :--- | :--- |
| **`card`** | `<x-ui::card title="Users" description="Manage accounts">{{ $slot }}</x-ui::card>` | `title`, `description`, `class`, `<x-slot:header>`, `<x-slot:footer>` |
| **`button`** | `<x-ui::button variant="primary" size="md" type="submit">Save</x-ui::button>` | `variant` (`primary`/`default`, `secondary`, `outline`, `ghost`, `destructive`), `size` (`sm`, `md`, `lg`), `href` |
| **`badge`** | `<x-ui::badge variant="success">Active</x-ui::badge>` | `variant` (`default`, `secondary`, `success`, `warning`, `error`/`destructive`, `outline`), `size` (`sm`, `md`) |
| **`input`** | `<x-ui::input name="email" label="Email" type="email" required wire:model="email" />` | `name`, `label`, `type`, `placeholder`, `required`, `disabled`, `helper`, `error`, `leadingIcon`, `trailingIcon` |
| **`select`** | `<x-ui::select name="role" label="Role" :options="['admin' => 'Admin']" wire:model="role" />` | `name`, `label`, `options` (associative array), `placeholder`, `required`, `disabled`, `helper`, `error` |
| **`textarea`** | `<x-ui::textarea name="bio" label="Bio" rows="4" wire:model="bio" />` | `name`, `label`, `rows`, `placeholder`, `required`, `disabled`, `helper`, `error` |
| **`checkbox`** | `<x-ui::checkbox name="remember" label="Remember me" wire:model="remember" />` | `name`, `label`, `description`, `checked`, `disabled`, `error` |
| **`toggle`** | `<x-ui::toggle name="active" label="Enable Notifications" wire:model="active" />` | `name`, `label`, `checked`, `disabled`, `error` |
| **`modal`** | `<x-ui::modal id="user-modal" x-show="open" title="Create User">{{ $slot }}</x-ui::modal>` | `id`, `title`, `size` (`sm`, `md`, `lg`, `xl`), `<x-slot:footer>` |
| **`alert`** | `<x-ui::alert type="success" title="Success" dismissible>Saved.</x-ui::alert>` | `type` (`info`, `success`, `warning`, `error`), `title`, `dismissible` |
| **`dropdown`** | `<x-ui::dropdown><x-slot:trigger>...</x-slot:trigger>{{ $slot }}</x-ui::dropdown>` | `align` (`left`, `right`, `center`), `width` (`w-48`, `w-56`), `<x-slot:trigger>` |
| **`table`** | `<x-ui::table><x-ui::table.header>...</x-ui::table.header><x-ui::table.row>...</x-ui::table.row></x-ui::table>` | Compound: `table`, `table.header`, `table.row`, `table.cell`, `table.empty` |

### Tier-2 Demand-Driven Extension Protocol

When a new feature or domain module requires an unlisted primitive (e.g. `tabs`, `avatar`, `sheet` / slide-over drawer, `skeleton`, `breadcrumb`, `tooltip`), adhere to the **Theme Parity Rule**:

1. **Dual-Theme Implementation**: Always implement matching templates in both `resources/views/themes/shadcn/components/<name>.blade.php` and `resources/views/themes/tailadmin/components/<name>.blade.php`.
2. **Identical Props & Slots API**: Both templates must accept identical props and slot names so switching themes via `php artisan ui:theme` never breaks consuming views.
3. **Automated Parity Tests**: Add component rendering assertions for both themes in `tests/Feature/ThemeComponentsTest.php`.
4. **Skill Documentation**: Add reference templates to `themes/shadcn/examples/<name>.blade.md` and `themes/tailadmin/examples/<name>.blade.md` and document the component in the catalog table above.

---

## 5. Critical Reactivity & Safety Rules

### 1. Error Handling & Isolated Rendering Safety
In all Blade components, always guard session `$errors` resolution to allow safe rendering inside unit tests, emails, and CLI tools:
```php
@php
$hasError = $error !== null || ($name && isset($errors) && $errors->has($name));
$errorMessage = $error ?? ($name && isset($errors) ? $errors->first($name) : null);
@endphp
```

### 2. Escaping Alpine Expressions on Blade Components
In standard HTML, `:disabled="loading"` passes to Alpine. Inside a Blade component (`<x-ui::button>`), Blade attempts PHP evaluation. Always escape Alpine bindings on Blade components using double colons or `x-bind:`:
```blade
{{-- Correct --}}
<x-ui::button ::disabled="loading" wire:click="save">
    Save
</x-ui::button>

{{-- Incorrect (Causes PHP Undefined constant 'loading' error) --}}
<x-ui::button :disabled="loading" wire:click="save">
    Save
</x-ui::button>
```

### 3. SPA Navigation
Always use `wire:navigate` and `wire:navigate.hover` on all internal links:
```blade
<a href="{{ route('admin.users.index') }}" wire:navigate wire:navigate.hover>Users</a>
```

---

## 6. Developer CLI Tooling

Switch themes dynamically via Artisan:

```bash
# Interactive selection prompt
php artisan ui:theme

# Direct theme switch
php artisan ui:theme shadcn
php artisan ui:theme tailadmin
```

This command automatically:
1. Validates the theme against `config('ui.themes')`.
2. Updates `APP_UI_THEME` in `.env`.
3. Flushes view and configuration caches (`view:clear`, `config:clear`).

---

## 7. Verification & Testing

Always verify theme changes using the automated test suite:

```bash
# Test Theme Engine and Switcher
php artisan rubberstamp:features --filter=ThemeEngineTest
php artisan rubberstamp:features --filter=ThemeSwitchCommandTest

# Test Component Rendering under both themes
php artisan rubberstamp:features --filter=ThemeComponentsTest
```
