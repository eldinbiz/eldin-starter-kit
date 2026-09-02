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
        <div class="mr-4 flex flex-col space-y-0.5">
            @if($label)
                <label @click="!@js($disabled) && (active = !active)" class="text-sm font-medium text-zinc-900 dark:text-zinc-100 cursor-pointer select-none">
                    {{ $label }}
                </label>
            @endif
            @if($description)
                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</span>
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
            :class="active ? 'bg-zinc-900 dark:bg-zinc-50' : 'bg-zinc-200 dark:bg-zinc-800'"
            class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus-visible:ring-zinc-300"
        >
            <span
                aria-hidden="true"
                :class="active ? 'translate-x-4 bg-white dark:bg-zinc-900' : 'translate-x-0 bg-white dark:bg-zinc-400'"
                class="pointer-events-none inline-block size-4 transform rounded-full shadow-xs ring-0 transition duration-200 ease-in-out"
            ></span>
        </button>
    </div>
</div>
