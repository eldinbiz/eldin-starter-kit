---
name: alpine-modal
description: "Apply this skill when building Alpine.js confirmation modal overlays with type-to-confirm for destructive actions (delete, disable, remove). Triggers: modal, confirmation, type-to-confirm, delete confirmation, disable confirmation, destructive action dialog, Alpine overlay. Features: blurred backdrop overlay, centered card, focus trapping (x-trap), click-outside dismissal, x-cloak flash prevention, dark mode support, conditional warning banners, type-to-confirm with disabled submit button, multi-theme <x-ui::modal> compatibility."
license: MIT
metadata:
  author: eldinbiz
---

# Alpine.js Confirmation Modal Pattern

This skill defines the canonical modal overlay pattern for destructive confirmation dialogs using Alpine.js and the abstract `<x-ui::*>` component layer.

---

## 1. Multi-Theme Type-to-Confirm Modal Pattern

The pattern uses a local `x-data` scope wrapping a trigger button and an abstract `<x-ui::modal>`. State is inline:

```blade
<div x-data="{ confirming: false, typedName: '' }">

    {{-- 1. Trigger --}}
    <x-ui::button
        variant="ghost"
        size="sm"
        type="button"
        @click="confirming = true; typedName = ''"
        class="text-red-500 hover:text-red-700"
    >
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
        </svg>
    </x-ui::button>

    {{-- 2. Theme-Agnostic Modal Overlay --}}
    <x-ui::modal
        x-show="confirming"
        @close="confirming = false; typedName = ''"
        title="Delete {{ $entityName }}?"
        size="md"
    >
        {{-- Description --}}
        <p class="text-center text-sm text-zinc-500 dark:text-zinc-400 mb-4">
            This action cannot be undone. The {{ $entityType }} <strong>{{ $entityName }}</strong> will be permanently removed.
        </p>

        {{-- Optional Warning Banner --}}
        @if ($affectedCount > 0)
            <x-ui::alert type="warning" class="mb-4 text-center">
                ⚠ {{ $affectedCount }} record(s) are currently associated with this {{ $entityType }}.
            </x-ui::alert>
        @endif

        {{-- Type-to-Confirm Instruction --}}
        <p class="text-center text-xs text-zinc-500 dark:text-zinc-400 mb-3">
            Type <code class="font-bold text-red-600 dark:text-red-400">{{ $entityName }}</code> to confirm.
        </p>

        {{-- Action Form --}}
        <form action="{{ $deleteRoute }}" method="POST" class="flex flex-col gap-4">
            @csrf
            @method('DELETE')

            {{-- Text Input --}}
            <x-ui::input
                x-model="typedName"
                placeholder="Type {{ $entityName }} to confirm"
                required
            />

            {{-- Footer Slot --}}
            <x-slot:footer>
                <x-ui::button
                    type="button"
                    variant="outline"
                    @click="confirming = false; typedName = ''"
                >
                    Cancel
                </x-ui::button>

                <x-ui::button
                    type="submit"
                    variant="destructive"
                    ::disabled="typedName !== '{{ $entityName }}'"
                >
                    Delete {{ $entityType }}
                </x-ui::button>
            </x-slot:footer>
        </form>
    </x-ui::modal>
</div>
```

---

## 2. Livewire v4 Component Integration

When integrating confirmation modals inside a **Livewire v4 Component** (e.g. `UserTable.php`), bind modal state to Livewire properties while keeping the modal container unconditionally in the DOM:

```blade
{{-- Livewire Confirmation Modal --}}
<x-ui::modal
    x-show="$wire.itemToDeleteId !== null"
    @close="$wire.cancelDelete()"
    title="Delete Item?"
    size="md"
>
    <div class="text-center space-y-3">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            Are you sure you want to delete <strong x-text="$wire.itemToDeleteName"></strong>? This action cannot be undone.
        </p>
    </div>

    <x-slot:footer>
        <x-ui::button
            type="button"
            variant="outline"
            wire:click="cancelDelete"
        >
            Cancel
        </x-ui::button>

        <x-ui::button
            type="button"
            variant="destructive"
            wire:click="deleteItem"
        >
            Delete
        </x-ui::button>
    </x-slot:footer>
</x-ui::modal>
```

---

## 3. Critical Alpine Binding Standard

> [!CAUTION]
> **Double-Colon Escaping on Blade Components (`::disabled`):**
> When passing Alpine expressions to Blade components (e.g. `<x-ui::button>`), **never use a single colon** (`:disabled="typedName !== 'admin'"`), as Blade will attempt PHP constant evaluation. Always use double colons (`::disabled="..."`) or `x-bind:disabled="..."`.

---

## 4. Key Directives Reference

| Directive | Purpose |
| :--- | :--- |
| `x-data="{ confirming: false, typedName: '' }"` | Local state: modal visibility + typed input string |
| `x-show="confirming"` | Toggle modal visibility |
| `x-cloak` | Prevent flash before Alpine initializes |
| `@close="..."` | Event listener dispatched by `<x-ui::modal>` on backdrop click or ESC key |
| `x-model="typedName"` | Two-way bind the confirmation text input |
| `::disabled="..."` | Blade-safe Alpine binding to disable action button until exact string match |

---

## 5. Rules

1. **Never use `onsubmit="return confirm(...)"`** — always use this Alpine modal pattern instead.
2. **Never mention "soft delete"** in user-facing text — just say "deleted" or "deletion".
3. **Always center-align** the description, warning, and confirmation prompt text.
4. **Always use `<x-ui::modal>` and `<x-ui::button>`** rather than hardcoding raw HTML/Tailwind overlays.
5. **Keep the `x-data` scope local** to the trigger + modal wrapper.
