@props(['align' => 'left'])

@php
$alignClass = match($align) {
    'right' => 'text-right',
    'center' => 'text-center',
    default => 'text-left',
};
@endphp

<th {{ $attributes->merge(['class' => "h-10 px-4 text-xs font-medium text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 {$alignClass}"]) }}>
    {{ $slot }}
</th>
