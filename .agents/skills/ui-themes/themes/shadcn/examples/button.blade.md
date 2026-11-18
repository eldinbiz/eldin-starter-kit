# shadcn Button Template (`resources/views/themes/shadcn/components/button.blade.php`)

```blade
@props([
    'variant' => 'default',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
$baseClasses = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 disabled:pointer-events-none disabled:opacity-50 dark:focus-visible:ring-zinc-300 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 cursor-pointer';

$variantClasses = match($variant) {
    'secondary' => 'bg-zinc-100 text-zinc-900 shadow-xs hover:bg-zinc-100/80 dark:bg-zinc-800 dark:text-zinc-50 dark:hover:bg-zinc-800/80',
    'outline' => 'border border-zinc-200 bg-white shadow-xs hover:bg-zinc-100 hover:text-zinc-900 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:bg-zinc-800 dark:hover:text-zinc-50',
    'ghost' => 'hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-zinc-50',
    'destructive' => 'bg-red-600 text-zinc-50 shadow-xs hover:bg-red-600/90 dark:bg-red-900 dark:text-zinc-50 dark:hover:bg-red-900/90',
    default => 'bg-zinc-900 text-zinc-50 shadow-xs hover:bg-zinc-900/90 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-50/90',
};

$sizeClasses = match($size) {
    'sm' => 'h-8 px-3 text-xs',
    'lg' => 'h-10 px-8 text-sm',
    'icon' => 'h-9 w-9',
    default => 'h-9 px-4 py-2 text-sm',
};

$classes = "{$baseClasses} {$variantClasses} {$sizeClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
```
