@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
])

@php
$variantClasses = match($variant) {
    'primary', 'default' => 'bg-brand-500 text-white hover:bg-brand-600 focus:ring-brand-500/30 shadow-theme-xs',
    'secondary' => 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700',
    'outline' => 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800',
    'destructive', 'error' => 'bg-error-500 text-white hover:bg-error-600 focus:ring-error-500/30',
    'ghost' => 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
    default => 'bg-brand-500 text-white hover:bg-brand-600',
};

$sizeClasses = match($size) {
    'sm' => 'px-3 py-1.5 text-xs',
    'lg' => 'px-5 py-3 text-base',
    'icon' => 'p-2',
    default => 'px-4 py-2 text-sm',
};
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => "inline-flex items-center justify-center font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 disabled:opacity-50 disabled:pointer-events-none cursor-pointer {$variantClasses} {$sizeClasses}"]) }}>
    {{ $slot }}
</button>
