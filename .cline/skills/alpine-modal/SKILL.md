---
name: alpine-modal
description: "Apply this skill when building Alpine.js confirmation modal overlays with type-to-confirm for destructive actions (delete, disable, remove). Triggers: modal, confirmation, type-to-confirm, delete confirmation, disable confirmation, destructive action dialog, Alpine overlay. Features: blurred backdrop overlay, centered card, focus trapping (x-trap), click-outside dismissal, x-cloak flash prevention, dark mode support, conditional warning banners, type-to-confirm with disabled submit button, Tailwind v4 styling."
license: MIT
metadata:
  author: eldinbiz
---

# Alpine.js Confirmation Modal Pattern

This skill defines the canonical modal overlay pattern for destructive confirmation dialogs. Use this exact structure whenever building a delete/disable confirmation modal in this project.

## Architecture

The pattern uses a single `x-data` scope wrapping a trigger button and a modal overlay. State is inline — no `Alpine.data()` required.

### Component Structure

```html
<div x-data="{ confirming: false, typedName: '' }">

    <!-- 1. Trigger -->
    <button type="button" @click="confirming = true; typedName = ''"
            class="text-gray-500 hover:text-error-500 ...">
        <!-- icon (trash, etc.) -->
    </button>

    <!-- 2. Overlay -->
    <div x-show="confirming" x-cloak x-trap.noscroll="confirming"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">

        <!-- 3. Card -->
        <div @click.outside="confirming = false; typedName = ''"
             class="mx-4 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl
                    dark:border-gray-700 dark:bg-white/[0.03]">

            <!-- 4. Header text (center-aligned) -->
            <p class="text-center text-sm font-medium text-gray-800 dark:text-white/90 mb-1">
                Delete {entity}?
            </p>

            <!-- 5. Description -->
            <p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-4">
                This action cannot be undone. The {entity} <strong>name</strong> will be deleted.
            </p>

            <!-- 6. Optional warning banner -->
            @if ($affectedCount > 0)
                <div class="mb-4 rounded-lg bg-warning-50 px-3 py-2 text-center dark:bg-warning-500/10">
                    <p class="text-xs text-warning-700 dark:text-warning-400">
                        ⚠ {X} user(s) are currently assigned to this {entity}.
                    </p>
                </div>
            @endif

            <!-- 7. Type-to-confirm instruction -->
            <p class="text-center text-xs text-gray-500 dark:text-gray-400 mb-3">
                Type <code>name</code> to confirm.
            </p>

            <!-- 8. Form -->
            <form action="{route}" method="POST">
                @csrf
                @method('DELETE')

                <!-- Text input -->
                <input type="text" x-model="typedName" placeholder="Type {entity} name"
                       class="mb-4 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2
                              text-sm text-gray-700 placeholder-gray-400
                              focus:border-error-500 focus:outline-none focus:ring-1 focus:ring-error-500
                              dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">

                <!-- 9. Button footer (right-aligned) -->
                <div class="flex justify-end gap-2">
                    <button type="button"
                            @click="confirming = false; typedName = ''"
                            class="inline-flex items-center justify-center rounded-lg border
                                   border-gray-300 bg-white px-4 py-2 text-sm font-medium
                                   text-gray-700 hover:bg-gray-50
                                   dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                        Cancel
                    </button>

                    <button type="submit"
                            :disabled="typedName !== '{exactName}'"
                            :class="typedName === '{exactName}'
                                ? 'bg-error-500 hover:bg-error-600 cursor-pointer'
                                : 'bg-gray-300 cursor-not-allowed dark:bg-gray-700'"
                            class="inline-flex items-center justify-center rounded-lg px-4 py-2
                                   text-sm font-medium text-white transition-colors">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

## Key Alpine Directives

| Directive | Purpose |
|---|---|
| `x-data="{ confirming: false, typedName: '' }"` | Local state: modal visibility + typed input |
| `x-show="confirming"` | Toggle overlay visibility |
| `x-cloak` | Prevent flash before Alpine initializes |
| `x-trap.noscroll="confirming"` | Trap focus inside modal; prevent body scroll |
| `@click.outside` | Dismiss modal when clicking outside the card |
| `x-model="typedName"` | Two-way bind the text input |
| `:disabled` | Disable submit until typed name matches expected value |
| `:class` | Toggle submit button styling based on match state |

## Design Tokens

- **Overlay backdrop**: `bg-black/50 backdrop-blur-sm`
- **Card**: `rounded-2xl`, `max-w-md`, `p-6`, `shadow-xl`
- **Warning banner**: `bg-warning-50` with `text-warning-700` text
- **Submit button active**: `bg-error-500 hover:bg-error-600`
- **Submit button disabled**: `bg-gray-300 cursor-not-allowed`
- **Cancel button**: outlined border style matching project buttons
- **Text input focus ring**: `focus:border-error-500 focus:ring-error-500`

## Dark Mode

All elements use `dark:` prefixed variants. The card background uses `dark:bg-white/[0.03]` (matching the project's dark mode convention). Warning banners use `dark:bg-warning-500/10`.

## Rules

1. **Never use `onsubmit="return confirm(...)"`** — always use this Alpine modal pattern instead.
2. **Never mention "soft delete"** in user-facing text — just say "deleted" or "deletion".
3. **Always center-align** the header, description, warning, and instruction text.
4. **Right-align** the Cancel/Delete button footer.
5. **Keep the `x-data` scope local** to the trigger+modal wrapper — do not place it on a parent element.
