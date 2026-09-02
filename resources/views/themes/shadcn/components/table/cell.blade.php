@props(['align' => 'left'])

@php
$alignClass = match($align) {
    'right' => 'text-right',
    'center' => 'text-center',
    default => 'text-left',
};
@endphp

<td {{ $attributes->merge(['class' => "p-4 align-middle text-sm text-zinc-900 dark:text-zinc-50 {$alignClass}"]) }}>
    {{ $slot }}
</td>
