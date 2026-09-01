@props([
    'variant' => 'default',
    'size' => 'default',
    'type' => 'button',
])

@php
$variantClasses = match($variant) {
    'default', 'primary' => 'bg-zinc-900 text-zinc-50 hover:bg-zinc-900/90 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-50/90 shadow-xs',
    'secondary' => 'bg-zinc-100 text-zinc-900 hover:bg-zinc-100/80 dark:bg-zinc-800 dark:text-zinc-50 dark:hover:bg-zinc-800/80 shadow-xs',
    'outline' => 'border border-zinc-200 bg-white hover:bg-zinc-100 hover:text-zinc-900 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:bg-zinc-800 dark:hover:text-zinc-50 shadow-xs',
    'ghost' => 'hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-zinc-50',
    'destructive', 'error' => 'bg-red-600 text-white hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700 shadow-xs',
    'link' => 'text-zinc-900 underline-offset-4 hover:underline dark:text-zinc-50',
    default => 'bg-zinc-900 text-zinc-50 hover:bg-zinc-900/90 dark:bg-zinc-50 dark:text-zinc-900 shadow-xs',
};

$sizeClasses = match($size) {
    'sm' => 'h-8 rounded-md px-3 text-xs',
    'lg' => 'h-10 rounded-md px-6 text-sm',
    'icon' => 'size-8 rounded-md p-0',
    default => 'h-9 rounded-md px-4 py-2 text-sm',
};
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => "inline-flex items-center justify-center font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 disabled:pointer-events-none disabled:opacity-50 dark:focus-visible:ring-zinc-300 cursor-pointer {$variantClasses} {$sizeClasses}"]) }}>
    {{ $slot }}
</button>
