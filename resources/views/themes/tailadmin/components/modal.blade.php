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
>
    <template x-teleport="body">
        <div
            x-show="show"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
            style="display: none;"
        >
            {{-- Backdrop --}}
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="show = false"
                class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity"
            ></div>

            {{-- Modal Card --}}
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div
                    x-show="show"
                    x-trap.inert.noscroll="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative w-full transform overflow-hidden rounded-2xl border border-gray-200 bg-white text-left shadow-2xl transition-all dark:border-gray-800 dark:bg-gray-900 {{ $maxWidthClasses }}"
                >
                    {{-- Header --}}
                    @if(isset($header) || $title)
                        <div class="flex items-start justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                            <div>
                                @if(isset($header))
                                    {{ $header }}
                                @else
                                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">
                                        {{ $title }}
                                    </h3>
                                    @if($description)
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $description }}
                                        </p>
                                    @endif
                                @endif
                            </div>

                            <button
                                type="button"
                                @click="show = false"
                                class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300 cursor-pointer"
                            >
                                <span class="sr-only">Close</span>
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @else
                        {{-- Close button if no header --}}
                        <button
                            type="button"
                            @click="show = false"
                            class="absolute right-4 top-4 rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300 cursor-pointer"
                        >
                            <span class="sr-only">Close</span>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endif

                    {{-- Body --}}
                    <div class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                        {{ $slot }}
                    </div>

                    {{-- Footer --}}
                    @if(isset($footer))
                        <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-3.5 dark:border-gray-800 dark:bg-gray-900/50">
                            {{ $footer }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>
