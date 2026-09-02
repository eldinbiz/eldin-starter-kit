@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
$typeConfig = match($type) {
    'success' => [
        'wrapper' => 'border-emerald-200/80 bg-emerald-50/40 text-emerald-950 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-200',
        'icon' => 'text-emerald-600 dark:text-emerald-400',
    ],
    'warning' => [
        'wrapper' => 'border-amber-200/80 bg-amber-50/40 text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-200',
        'icon' => 'text-amber-600 dark:text-amber-400',
    ],
    'error', 'destructive' => [
        'wrapper' => 'border-red-200/80 bg-red-50/40 text-red-950 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-200',
        'icon' => 'text-red-600 dark:text-red-400',
    ],
    default => [
        'wrapper' => 'border-zinc-200 bg-zinc-50/50 text-zinc-950 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-50',
        'icon' => 'text-zinc-900 dark:text-zinc-100',
    ],
};
@endphp

<div
    x-data="{ dismissed: false }"
    x-show="!dismissed"
    x-transition
    {{ $attributes->merge(['class' => "relative w-full rounded-lg border p-4 shadow-xs [&>svg~*]:pl-7 [&>svg+div]:translate-y-[-3px] [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4 {$typeConfig['wrapper']}"]) }}
>
    <div class="{{ $typeConfig['icon'] }}">
        @if($type === 'success')
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        @elseif($type === 'warning')
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        @elseif($type === 'error' || $type === 'destructive')
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        @else
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        @endif
    </div>

    <div class="space-y-1">
        @if($title)
            <h5 class="font-medium leading-none tracking-tight text-sm">{{ $title }}</h5>
        @endif
        <div class="text-xs [&_p]:leading-relaxed opacity-90">{{ $slot }}</div>
    </div>

    @if($dismissible)
        <button
            type="button"
            @click="dismissed = true"
            class="absolute right-3 top-3 rounded-sm opacity-70 transition-opacity hover:opacity-100 focus:outline-none"
        >
            <span class="sr-only">Dismiss</span>
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
