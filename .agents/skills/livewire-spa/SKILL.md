---
name: livewire-spa
description: "Apply this skill when creating, scaffolding, modifying, refactoring, or testing reactive Livewire v4 Single Page Application (SPA) components in this repository. Covers Livewire v4 Single-File Components (SFC), Page components, 3-layer Domain Action delegation, wire:navigate, reactive state, and Pest testing."
license: MIT
metadata:
  author: eldinbiz
---

# Livewire v4 Single Page Application (SPA) Standard

This skill defines the canonical pattern for building, structuring, and testing **Livewire v4** Single Page Application (SPA) components in this repository using modern **Single-File Components (SFC)** and the **3-Layer Architecture**.

---

## 1. Core Principles

1. **Livewire v4 SFC Architecture**: Components use Single-File Components (`resources/views/components/⚡*.blade.php` or `resources/views/components/<module>/⚡*.blade.php`) or Full Page SFCs (`resources/views/pages/⚡*.blade.php`), uniting state, validation, and Blade template in a single cohesive file.
2. **3-Layer Architecture (Domain Action Delegation)**: Livewire components are thin UI orchestrators. State mutations (create, update, delete, submit) **must delegate** to single-purpose **Domain Actions** (`App\Actions\*` or `Modules\<Module>\Actions\*`) via method injection (`$action->execute(...)`). Never write raw multi-table Eloquent transactions inside component classes.
3. **SPA Zero-Reload Navigation**: All internal navigation links must use `wire:navigate` and `wire:navigate.hover` for instant client-side page transitions without page refreshes.
4. **Bundled Alpine.js Core**: Livewire v4 automatically bundles and initializes Alpine.js v3. Do not add standalone Alpine CDN script tags to Blade layout templates.
5. **Islands & Real-Time Reactivity**: Leverage `@island` for isolated component sub-regions and `wire:stream` for real-time streaming operations.

---

## 2. Component Structure Patterns

### A. Single-File Component (SFC) with Domain Action Delegation
Location: `resources/views/components/⚡component-name.blade.php` or `resources/views/components/<module>/<component>.blade.php`

```blade
<?php

use App\Actions\Items\CreateItemAction;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

new class extends Component
{
    #[Url(history: true)]
    public string $search = '';

    #[Validate('required|string|max:255')]
    public string $title = '';

    public function save(CreateItemAction $action): void
    {
        $validated = $this->validate();

        // 3-Layer Pattern: Delegate to Domain Action
        $record = $action->execute($validated);

        $this->dispatch('saved', message: 'Record saved successfully.');
    }
};
?>

<x-ui::card title="Create Item" description="Fill in item details">
    <form wire:submit="save" class="flex flex-col gap-4">
        <x-ui::input
            name="title"
            label="Title"
            required
            wire:model="title"
        />

        <div class="flex justify-end">
            <x-ui::button type="submit" variant="primary">
                Save
            </x-ui::button>
        </div>
    </form>
</x-ui::card>
```

### B. Full-Page SFC Component
Location: `resources/views/pages/⚡page-name.blade.php`
Route definition:
```php
use Illuminate\Support\Facades\Route;

Route::livewire('/settings/general', 'pages::general-settings')->name('settings.general');
```

---

## 3. SPA Navigation (`wire:navigate`)

Inside layouts, navigation menus, and tab headers, always use `wire:navigate` and `wire:navigate.hover`:

```blade
<nav class="flex gap-2">
    <a
        href="{{ route('settings.general') }}"
        wire:navigate
        wire:navigate.hover
        class="{{ request()->routeIs('settings.general') ? 'bg-brand-500 text-white' : 'text-gray-600 hover:bg-gray-100' }} rounded-lg px-3 py-2 text-sm font-medium"
    >
        General Settings
    </a>
</nav>
```

---

## 4. Pest 5 Testing Standard

Livewire v4 SFC components are tested seamlessly in Pest 5:

```php
<?php

declare(strict_types=1);

use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders and saves data via single file component', function (): void {
    Livewire::test('general-settings')
        ->assertSuccessful()
        ->set('title', 'New System Title')
        ->call('save')
        ->assertDispatched('saved');
});
```
