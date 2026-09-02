# Multi-Theme UI Architecture & Developer Guide

This application features a dynamic, pluggable **Multi-Theme UI Architecture**. Developers can switch the entire application's look and feel between **TailAdmin** (modern, rounded cards, rich badges) and **shadcn** (minimalist, high-density, crisp 1px borders) with zero template code duplication.

---

## 1. Quick Start & Theme Switching

### Switching via Artisan CLI
You can switch themes interactively or by passing a theme name directly:

```bash
# Interactive selection prompt
php artisan ui:theme

# Direct theme switch
php artisan ui:theme shadcn
php artisan ui:theme tailadmin
```

The CLI command automatically:
1. Updates `APP_UI_THEME` in your `.env` file.
2. Clears compiled Blade views (`php artisan view:clear`).
3. Flushes configuration cache (`php artisan config:clear`).

### Switching via Environment Variable
Set the `APP_UI_THEME` variable in `.env`:
```env
APP_UI_THEME=shadcn
# or
APP_UI_THEME=tailadmin
```

---

## 2. Architecture Overview

```
resources/views/
├── themes/
│   ├── tailadmin/
│   │   ├── layouts/ (app.blade.php, auth.blade.php)
│   │   ├── partials/ (sidebar, header, breadcrumbs)
│   │   └── components/ (card, button, input, modal, ...)
│   └── shadcn/
│       ├── layouts/ (app.blade.php, auth.blade.php)
│       ├── partials/ (sidebar, header, command-palette)
│       └── components/ (card, button, input, modal, ...)
├── layouts/
│   ├── app.blade.php (Proxy -> theme::layouts.app)
│   └── auth.blade.php (Proxy -> theme::layouts.auth)
```

### Key Components:
- **`ThemeServiceProvider`**: Reads `config('ui.theme')`, dynamically registers the `theme` view namespace (`resources/views/themes/{activeTheme}`), and registers the `<x-ui::*>` anonymous component namespace (`theme::components`).
- **Layout Proxies**: `layouts.app` and `layouts.auth` proxy directly to `theme::layouts.app` and `theme::layouts.auth`.
- **`@themeIs('theme_name')` Directive**: Conditional Blade directive for rare theme-specific edge cases.

---

## 3. Abstract UI Component Catalog (`<x-ui::*>`)

All application pages and domain modules (`app-modules/`) MUST use `<x-ui::*>` components instead of raw hardcoded Tailwind classes.

### A. Containers & Cards

#### `<x-ui::card>`
Card container with optional title, description, and footer slot.
```blade
<x-ui::card title="Project Details" description="Manage project configurations.">
    <p>Main content goes here.</p>
    
    <x-slot:footer>
        <x-ui::button variant="primary">Save Changes</x-ui::button>
    </x-slot:footer>
</x-ui::card>
```

#### `<x-ui::modal>`
Accessible Alpine.js modal dialog with focus trapping (`x-trap`), backdrop blur, and ESC key listener.
```blade
<div x-data="{ openModal: false }">
    <x-ui::button @click="openModal = true">Open Dialog</x-ui::button>

    <x-ui::modal
        id="confirm-dialog"
        title="Confirm Deletion"
        description="Are you sure you want to delete this resource?"
        x-show="openModal"
        @close="openModal = false"
    >
        <p class="text-sm text-gray-500">This action cannot be undone.</p>

        <x-slot:footer>
            <x-ui::button variant="outline" @click="openModal = false">Cancel</x-ui::button>
            <x-ui::button variant="destructive" wire:click="delete">Delete</x-ui::button>
        </x-slot:footer>
    </x-ui::modal>
</div>
```

---

### B. Form Controls

#### `<x-ui::input>`
Text, email, number, and password inputs with labels, icons, helper text, and validation error messages.
```blade
<x-ui::input
    type="email"
    id="email"
    name="email"
    label="Email Address"
    placeholder="user@example.com"
    required
    helper="We will never share your email address."
    wire:model="email"
/>
```

#### `<x-ui::select>`
Dropdown select with array options or custom `<option>` slots.
```blade
<x-ui::select
    id="status"
    name="status"
    label="Account Status"
    :options="['active' => 'Active', 'pending' => 'Pending', 'disabled' => 'Disabled']"
    wire:model="status"
/>
```

#### `<x-ui::textarea>`
Multiline textarea with rows and validation state.
```blade
<x-ui::textarea
    id="notes"
    name="notes"
    label="Internal Notes"
    rows="4"
    placeholder="Add any extra details here..."
    wire:model="notes"
/>
```

#### `<x-ui::checkbox>`
Accessible checkbox with label, description, and state binding.
```blade
<x-ui::checkbox
    id="newsletter"
    name="newsletter"
    label="Subscribe to newsletter"
    description="Receive weekly product updates."
    wire:model="newsletter"
/>
```

#### `<x-ui::toggle>`
Reactive Alpine.js switch control.
```blade
<x-ui::toggle
    id="notifications"
    name="notifications"
    label="Enable Email Notifications"
    wire:model="notifications"
/>
```

---

### C. Actions, Badges & Feedback

#### `<x-ui::button>`
Button with multiple variants (`primary`, `secondary`, `outline`, `ghost`, `destructive`) and sizes (`sm`, `md`, `lg`).
```blade
<x-ui::button variant="primary" type="submit">Save</x-ui::button>
<x-ui::button variant="outline" href="{{ route('admin.users.index') }}">Cancel</x-ui::button>
<x-ui::button variant="destructive" size="sm" wire:click="delete">Delete</x-ui::button>
```

#### `<x-ui::badge>`
Status and label indicators (`brand`, `success`, `warning`, `danger`, `neutral`).
```blade
<x-ui::badge variant="success">Approved</x-ui::badge>
<x-ui::badge variant="warning">Pending Review</x-ui::badge>
<x-ui::badge variant="danger">Rejected</x-ui::badge>
<x-ui::badge variant="neutral">Draft</x-ui::badge>
```

#### `<x-ui::alert>`
Status banner with dismissible option.
```blade
<x-ui::alert type="success" title="Success!" dismissible>
    Your profile information has been updated.
</x-ui::alert>

<x-ui::alert type="error" title="Validation Failed">
    Please correct the errors in the form below.
</x-ui::alert>
```

#### `<x-ui::dropdown>`
Dropdown action menu.
```blade
<x-ui::dropdown align="right">
    <x-slot:trigger>
        <x-ui::button variant="outline" size="sm">Actions ▾</x-ui::button>
    </x-slot:trigger>

    <a href="#" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800">Edit</a>
    <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-800">Delete</a>
</x-ui::dropdown>
```

---

### D. Compound Data Tables

```blade
<x-ui::table>
    <thead>
        <x-ui::table.row>
            <x-ui::table.header>NAME</x-ui::table.header>
            <x-ui::table.header>EMAIL</x-ui::table.header>
            <x-ui::table.header>STATUS</x-ui::table.header>
            <x-ui::table.header align="right">ACTIONS</x-ui::table.header>
        </x-ui::table.row>
    </thead>
    <tbody>
        @forelse($users as $user)
            <x-ui::table.row>
                <x-ui::table.cell class="font-medium">{{ $user->name }}</x-ui::table.cell>
                <x-ui::table.cell>{{ $user->email }}</x-ui::table.cell>
                <x-ui::table.cell>
                    <x-ui::badge variant="success">Active</x-ui::badge>
                </x-ui::table.cell>
                <x-ui::table.cell align="right">
                    <x-ui::button size="sm" variant="ghost" href="{{ route('admin.users.edit', $user->id) }}">
                        Edit
                    </x-ui::button>
                </x-ui::table.cell>
            </x-ui::table.row>
        @empty
            <x-ui::table.empty colspan="4" message="No users found." />
        @endforelse
    </tbody>
</x-ui::table>
```

---

## 4. Guidelines for Module Developers

When authoring new domain modules in `app-modules/<module-name>/`:

1. **Never hardcode theme-specific classes**: Avoid classes like `rounded-2xl` or `shadow-theme-sm` directly in view markup. Use `<x-ui::card>` and `<x-ui::input>`.
2. **Use Semantic Tokens for Custom CSS**: If writing custom Blade templates, use neutral CSS tokens (`text-gray-800 dark:text-white/90` or `text-zinc-900 dark:text-zinc-100`).
3. **Always use SPA navigation**: Include `wire:navigate` on internal navigation links.

---

## 5. Adding a New Theme

To add a new theme (e.g., `modern` or `fluent`):

1. Create the theme directory: `resources/views/themes/newtheme/`
2. Create layouts: `resources/views/themes/newtheme/layouts/app.blade.php` and `auth.blade.php`.
3. Create atomic components: `resources/views/themes/newtheme/components/` (`card`, `button`, `input`, etc.).
4. Register the theme in `config/ui.php`:
   ```php
   'themes' => [
       'tailadmin' => [...],
       'shadcn'    => [...],
       'newtheme'  => [
           'name' => 'New Theme',
           'description' => 'Custom theme description',
           'views_path' => resource_path('views/themes/newtheme'),
       ],
   ],
   ```
5. Test switching to your new theme: `php artisan ui:theme newtheme`.
