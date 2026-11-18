# shadcn Modal Template (`resources/views/themes/shadcn/components/modal.blade.php`)

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
            {{ $attributes->merge(['class' => 'fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4 sm:p-6']) }}
            x-trap.inert="$el.style.display !== 'none'"
        >
    {{-- Backdrop with blur --}}
    <div
        class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity"
        @click="$dispatch('close')"
    ></div>

    {{-- Modal Card --}}
    <div
        class="relative w-full {{ $sizeClasses }} rounded-lg border border-zinc-200 bg-white p-6 shadow-lg duration-200 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50"
        @click.stop
    >
        @if ($title || $description)
            <div class="flex flex-col space-y-1.5 text-center sm:text-left mb-4">
                @if ($title)
                    <h2 class="text-lg font-semibold leading-none tracking-tight">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                @endif
            </div>
        @endif

        {{-- Close button --}}
        <button
            type="button"
            class="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-white transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-zinc-950 focus:ring-offset-2 dark:ring-offset-zinc-950 dark:focus:ring-zinc-300 cursor-pointer"
            @click="$dispatch('close')"
        >
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
            <span class="sr-only">Close</span>
        </button>

        <div class="text-sm text-zinc-700 dark:text-zinc-300">
            {{ $slot }}
        </div>

        @if ($footer)
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2 mt-6">
                {{ $footer }}
            </div>
        @endif
    </div>
    </div>
    </template>
</div>
```
