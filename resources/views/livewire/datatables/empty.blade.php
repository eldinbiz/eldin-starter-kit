@props([
    'title' => 'No records found',
    'message' => 'Try adjusting your search query or removing active filters.',
])

<div class="flex flex-col items-center justify-center py-12 px-4 text-center">
    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 mb-3">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
    </div>
    <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-1">
        {{ $title }}
    </h3>
    <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mb-4">
        {{ $message }}
    </p>
    @if ($this->activeFilterCount() > 0)
        <button
            type="button"
            wire:click="resetFilters"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer shadow-theme-xs transition"
        >
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
            Clear all filters
        </button>
    @endif
</div>
