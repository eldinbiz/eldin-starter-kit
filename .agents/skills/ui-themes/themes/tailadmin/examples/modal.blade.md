# TailAdmin Modal Template (`resources/views/themes/tailadmin/components/modal.blade.php`)

```blade
@props([
    'id' => null,
    'title' => null,
    'description' => null,
    'size' => 'md',
    'footer' => null,
])

@php
$sizeClasses = match($size) {
    'sm' => 'max-w-sm',
    'lg' => 'max-w-2xl',
    'xl' => 'max-w-4xl',
    'full' => 'max-w-6xl',
    default => 'max-w-lg',
};
@endphp

<div
    @if($id) id="{{ $id }}" @endif
    x-cloak
    @keydown.escape.window="$dispatch('close')"
>
    <template x-teleport="body">
        <div
            {{ $attributes->merge(['class' => 'fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-4 sm:p-6']) }}
            x-trap.inert="$el.style.display !== 'none'"
        >
            {{-- Backdrop with blur --}}
            <div
                class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
                @click="$dispatch('close')"
            ></div>

            {{-- Modal Card --}}
            <div
                class="relative w-full {{ $sizeClasses }} rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                @click.stop
            >
                @if ($title || $description)
                    <div class="mb-4">
                        @if ($title)
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h3>
                        @endif
                        @if ($description)
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
                        @endif
                    </div>
                @endif

                {{-- Close button --}}
                <button
                    type="button"
                    class="absolute right-4 top-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer"
                    @click="$dispatch('close')"
                >
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    <span class="sr-only">Close</span>
                </button>

                <div class="text-sm text-gray-600 dark:text-gray-300">
                    {{ $slot }}
                </div>

                @if ($footer)
                    <div class="mt-6 flex items-center justify-end gap-3">
                        {{ $footer }}
                    </div>
                @endif
            </div>
        </div>
    </template>
</div>
```
