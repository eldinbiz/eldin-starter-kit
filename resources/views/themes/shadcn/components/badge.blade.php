@props(['variant' => 'default'])

@php
$variantClasses = match($variant) {
    'default', 'primary' => 'border-transparent bg-zinc-900 text-zinc-50 dark:bg-zinc-50 dark:text-zinc-900 shadow-2xs',
    'secondary', 'gray' => 'border-transparent bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-50',
    'outline' => 'border-zinc-200 text-zinc-900 dark:border-zinc-800 dark:text-zinc-50',
    'destructive', 'error' => 'border-transparent bg-red-600 text-white shadow-2xs',
    'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400',
    'warning' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-400',
    default => 'border-transparent bg-zinc-900 text-zinc-50',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-zinc-950 focus:ring-offset-2 dark:focus:ring-zinc-300 {$variantClasses}"]) }}>
    {{ $slot }}
</span>
