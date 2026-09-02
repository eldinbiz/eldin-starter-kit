@props([
    'align' => 'right',
    'width' => '48',
])

@php
$alignmentClasses = match($align) {
    'left' => 'origin-top-left left-0',
    'top-left' => 'origin-bottom-left left-0 bottom-full mb-2',
    'top-right' => 'origin-bottom-right right-0 bottom-full mb-2',
    default => 'origin-top-right right-0',
};

$widthClasses = match($width) {
    '48' => 'w-48',
    '56' => 'w-56',
    '64' => 'w-64',
    default => $width,
};
@endphp

<div
    x-data="{ open: false }"
    @click.outside="open = false"
    @close.stop="open = false"
    class="relative inline-block text-left"
>
    <div @click="open = !open">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 mt-1 {{ $widthClasses }} rounded-md border border-zinc-200 bg-white p-1 text-zinc-950 shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50 {{ $alignmentClasses }}"
        style="display: none;"
        @click="open = false"
    >
        <div class="space-y-0.5 text-xs">
            {{ $slot }}
        </div>
    </div>
</div>
