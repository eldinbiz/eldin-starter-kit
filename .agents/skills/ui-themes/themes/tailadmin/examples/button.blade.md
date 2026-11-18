# TailAdmin Button Template (`resources/views/themes/tailadmin/components/button.blade.php`)

```blade
@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium gap-2 transition focus:outline-hidden disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer';

$variantClasses = match($variant) {
    'secondary' => 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 shadow-theme-xs',
    'outline' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 shadow-theme-xs',
    'ghost' => 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800',
    'destructive' => 'bg-error-500 text-white hover:bg-error-600 shadow-theme-xs',
    default => 'bg-brand-500 text-white hover:bg-brand-600 shadow-theme-xs',
};

$sizeClasses = match($size) {
    'sm' => 'px-3 py-1.5 text-xs rounded-md',
    'lg' => 'px-6 py-3.5 text-base rounded-xl',
    'icon' => 'p-2 rounded-lg',
    default => 'px-4 py-2.5 text-sm rounded-lg',
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
