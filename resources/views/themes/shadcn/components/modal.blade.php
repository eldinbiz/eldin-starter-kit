@props([
    'id' => null,
    'name' => null,
    'show' => null,
    'title' => null,
    'description' => null,
    'maxWidth' => 'md',
])

@php
$modalName = $name ?? $id ?? 'modal-' . uniqid();

$maxWidthClasses = match($maxWidth) {
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    '3xl' => 'sm:max-w-3xl',
    '4xl' => 'sm:max-w-4xl',
    '5xl' => 'sm:max-w-5xl',
    'full' => 'sm:max-w-full sm:m-4',
    default => 'sm:max-w-md',
};
@endphp

<div
    x-data="{ show: @if($show) {{ $show }} @else false @endif }"
    x-on:open-modal.window="$event.detail == '{{ $modalName }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $modalName }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="show = false"
        class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"
    ></div>

    {{-- Modal Dialog Card --}}
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div
            x-show="show"
            x-trap.inert.noscroll="show"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full transform overflow-hidden rounded-lg border border-zinc-200 bg-white p-6 text-left shadow-lg transition-all dark:border-zinc-800 dark:bg-zinc-950 {{ $maxWidthClasses }}"
        >
            {{-- Close X --}}
            <button
                type="button"
                @click="show = false"
                class="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-zinc-950 focus:ring-offset-2 dark:focus:ring-zinc-300"
            >
                <span class="sr-only">Close</span>
                <svg class="size-4 text-zinc-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            {{-- Header --}}
            @if(isset($header) || $title)
                <div class="flex flex-col space-y-1.5 text-left mb-4">
                    @if(isset($header))
                        {{ $header }}
                    @else
                        <h3 class="text-base font-semibold leading-none tracking-tight text-zinc-900 dark:text-zinc-50">
                            {{ $title }}
                        </h3>
                        @if($description)
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $description }}
                            </p>
                        @endif
                    @endif
                </div>
            @endif

            {{-- Body --}}
            <div class="text-sm text-zinc-700 dark:text-zinc-300">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            @if(isset($footer))
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2 mt-6 gap-2 sm:gap-0">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
