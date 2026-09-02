@props(['align' => 'left'])

@php
$alignClass = match($align) {
    'right' => 'text-right',
    'center' => 'text-center',
    default => 'text-left',
};
@endphp

<th {{ $attributes->merge(['class' => "bg-gray-50/50 px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-800/50 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800 {$alignClass}"]) }}>
    {{ $slot }}
</th>
