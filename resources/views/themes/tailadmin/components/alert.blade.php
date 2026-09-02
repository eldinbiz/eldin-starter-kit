@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
$typeConfig = match($type) {
    'success' => [
        'wrapper' => 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
        'icon' => 'text-emerald-500 dark:text-emerald-400',
    ],
    'warning' => [
        'wrapper' => 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
        'icon' => 'text-amber-500 dark:text-amber-400',
    ],
    'error', 'destructive' => [
        'wrapper' => 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
        'icon' => 'text-rose-500 dark:text-rose-400',
    ],
    default => [
        'wrapper' => 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800',
        'icon' => 'text-blue-500 dark:text-blue-400',
    ],
};
@endphp

<div
    x-data="{ dismissed: false }"
    x-show="!dismissed"
    x-transition
    {{ $attributes->merge(['class' => "flex items-start rounded-xl border p-4 shadow-theme-xs {$typeConfig['wrapper']}"]) }}
>
    <div class="mr-3 shrink-0 pt-0.5 {{ $typeConfig['icon'] }}">
        @if($type === 'success')
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        @elseif($type === 'warning')
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        @elseif($type === 'error' || $type === 'destructive')
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        @else
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        @endif
    </div>

    <div class="flex-1 text-sm">
        @if($title)
            <h5 class="mb-1 font-semibold leading-none tracking-tight">{{ $title }}</h5>
        @endif
        <div class="leading-relaxed opacity-90">{{ $slot }}</div>
    </div>

    @if($dismissible)
        <button
            type="button"
            @click="dismissed = true"
            class="ml-3 inline-flex rounded-lg p-1 opacity-70 hover:opacity-100 focus:outline-none"
        >
            <span class="sr-only">Dismiss</span>
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
