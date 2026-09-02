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
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 mt-2 {{ $widthClasses }} rounded-2xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900 {{ $alignmentClasses }}"
        style="display: none;"
        @click="open = false"
    >
        <div class="space-y-0.5 text-sm text-gray-700 dark:text-gray-200">
            {{ $slot }}
        </div>
    </div>
</div>
