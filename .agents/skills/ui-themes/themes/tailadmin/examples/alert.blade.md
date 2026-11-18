# TailAdmin Alert Template (`resources/views/themes/tailadmin/components/alert.blade.php`)

```blade
@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
$classes = match($type) {
    'success' => 'bg-success-50 border-success-200 text-success-800 dark:bg-success-500/10 dark:border-success-500/20 dark:text-success-400',
    'warning' => 'bg-warning-50 border-warning-200 text-warning-800 dark:bg-warning-500/10 dark:border-warning-500/20 dark:text-warning-400',
    'error' => 'bg-error-50 border-error-200 text-error-800 dark:bg-error-500/10 dark:border-error-500/20 dark:text-error-400',
    default => 'bg-brand-50 border-brand-200 text-brand-800 dark:bg-brand-500/10 dark:border-brand-500/20 dark:text-brand-400',
};
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    {{ $attributes->merge(['class' => "flex items-start gap-3 p-4 rounded-xl border {$classes}"]) }}
    role="alert"
>
    <div class="shrink-0 mt-0.5">
        @if ($type === 'success')
            <svg class="size-5 text-success-600 dark:text-success-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
            </svg>
        @elseif ($type === 'warning')
            <svg class="size-5 text-warning-600 dark:text-warning-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
        @elseif ($type === 'error')
            <svg class="size-5 text-error-600 dark:text-error-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
            </svg>
        @else
            <svg class="size-5 text-brand-600 dark:text-brand-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
            </svg>
        @endif
    </div>

    <div class="flex-1">
        @if ($title)
            <h5 class="text-sm font-semibold mb-1">{{ $title }}</h5>
        @endif
        <div class="text-sm leading-relaxed">
            {{ $slot }}
        </div>
    </div>

    @if ($dismissible)
        <button
            type="button"
            class="shrink-0 p-1 rounded-md opacity-70 hover:opacity-100 transition cursor-pointer"
            @click="show = false"
        >
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
            </svg>
        </button>
    @endif
</div>
```
