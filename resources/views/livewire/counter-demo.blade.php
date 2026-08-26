<div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900">
    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
        <div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                Livewire Reactive Counter
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Server-driven reactivity powered by Livewire v3/v4 & Alpine.js
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            Active
        </span>
    </div>

    <div class="py-6 text-center">
        <span class="text-5xl font-bold tracking-tight text-gray-900 dark:text-white font-mono" wire:key="count-display">
            {{ $count }}
        </span>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Current Value</p>
    </div>

    <div class="flex items-center justify-center gap-3">
        <button
            type="button"
            wire:click="decrement"
            wire:loading.attr="disabled"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
        >
            - Decrement
        </button>

        <button
            type="button"
            wire:click="resetCount"
            wire:loading.attr="disabled"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-500 shadow-xs hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
        >
            Reset
        </button>

        <button
            type="button"
            wire:click="increment"
            wire:loading.attr="disabled"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-xs hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 disabled:opacity-50"
        >
            + Increment
        </button>
    </div>

    <div wire:loading class="mt-4 flex items-center justify-center gap-2 text-xs text-brand-500">
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        <span>Updating state...</span>
    </div>
</div>
