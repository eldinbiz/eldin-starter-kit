# shadcn Alert Template (`resources/views/themes/shadcn/components/alert.blade.php`)

```blade
@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
$classes = match($type) {
    'success' => 'border-emerald-500/50 text-emerald-700 dark:border-emerald-500/30 dark:text-emerald-400 [&>svg]:text-emerald-600',
    'warning' => 'border-amber-500/50 text-amber-700 dark:border-amber-500/30 dark:text-amber-400 [&>svg]:text-amber-600',
    'error' => 'border-red-500/50 text-red-700 dark:border-red-500/30 dark:text-red-400 [&>svg]:text-red-600',
    default => 'border-zinc-200 text-zinc-950 dark:border-zinc-800 dark:text-zinc-50 [&>svg]:text-zinc-950 dark:[&>svg]:text-zinc-50',
};
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    {{ $attributes->merge(['class' => "relative w-full rounded-lg border p-4 [&>svg~*]:pl-7 [&>svg+div]:translate-y-[-3px] [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4 {$classes}"]) }}
    role="alert"
>
    @if ($type === 'success')
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    @elseif ($type === 'warning')
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    @elseif ($type === 'error')
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    @else
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
    @endif

    <div>
        @if ($title)
            <h5 class="mb-1 font-medium leading-none tracking-tight">{{ $title }}</h5>
        @endif
        <div class="text-sm [&_p]:leading-relaxed">
            {{ $slot }}
        </div>
    </div>

    @if ($dismissible)
        <button
            type="button"
            class="absolute right-3 top-3 rounded-md p-1 opacity-70 transition-opacity hover:opacity-100 cursor-pointer"
            @click="show = false"
        >
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            <span class="sr-only">Dismiss</span>
        </button>
    @endif
</div>
```
