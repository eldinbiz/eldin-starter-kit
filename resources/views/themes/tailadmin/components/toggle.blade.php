@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'description' => null,
    'checked' => false,
    'disabled' => false,
])

@php
$toggleId = $id ?? $name ?? 'toggle-' . uniqid();
@endphp

<div
    x-data="{ active: @js((bool) $checked) }"
    class="flex items-center justify-between"
>
    @if($label || $description)
        <div class="mr-4 flex flex-col">
            @if($label)
                <label @click="!@js($disabled) && (active = !active)" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer select-none">
                    {{ $label }}
                </label>
            @endif
            @if($description)
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $description }}</span>
            @endif
        </div>
    @endif

    <div class="relative inline-flex items-center">
        @if($name)
            <input
                type="checkbox"
                name="{{ $name }}"
                id="{{ $toggleId }}"
                value="1"
                :checked="active"
                @if($disabled) disabled @endif
                class="sr-only"
                {{ $attributes }}
            />
        @endif

        <button
            type="button"
            role="switch"
            :aria-checked="active.toString()"
            @click="!@js($disabled) && (active = !active)"
            @if($disabled) disabled @endif
            :class="active ? 'bg-brand-500' : 'bg-gray-200 dark:bg-gray-700'"
            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
        >
            <span
                aria-hidden="true"
                :class="active ? 'translate-x-5' : 'translate-x-0'"
                class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out"
            ></span>
        </button>
    </div>
</div>
