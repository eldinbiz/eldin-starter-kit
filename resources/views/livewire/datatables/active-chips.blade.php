@props([
    'filterLabels' => [],
])

@if ($this->activeFilterCount() > 0)
    <div class="flex flex-wrap items-center gap-2 mb-4 pt-1">
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
            Active Filters:
        </span>

        {{-- Search Chip --}}
        @if ($this->search !== '')
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 dark:bg-brand-500/10 dark:text-brand-400 border border-brand-200 dark:border-brand-500/20">
                <span>Search: "<strong>{{ $this->search }}</strong>"</span>
                <button
                    type="button"
                    wire:click="$set('search', '')"
                    class="rounded-full p-0.5 hover:bg-brand-200 dark:hover:bg-brand-500/30 text-brand-600 dark:text-brand-300 transition cursor-pointer"
                    title="Remove search"
                >
                    <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </span>
        @endif

        {{-- Custom Filter Chips --}}
        @foreach ($this->filterValues as $key => $val)
            @if ($val !== null && $val !== '' && $val !== [])
                @php
                    $label = $filterLabels[$key] ?? ucfirst(str_replace(['filter_', '_'], ['', ' '], $key));
                    $displayVal = is_array($val) ? implode(', ', $val) : (string) $val;
                    if ($key === 'trashed' && ($val === true || $val === '1' || $val === 1)) {
                        $displayVal = 'Show deleted only';
                    }
                @endphp
                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                    <span>{{ $label }}: <strong>{{ $displayVal }}</strong></span>
                    <button
                        type="button"
                        wire:click="removeFilter('{{ $key }}')"
                        class="rounded-full p-0.5 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 transition cursor-pointer"
                        title="Remove {{ $label }}"
                    >
                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </span>
            @endif
        @endforeach

        {{-- Clear All Button --}}
        <button
            type="button"
            wire:click="resetFilters"
            class="text-xs font-medium text-error-600 hover:text-error-700 dark:text-error-400 dark:hover:text-error-300 underline cursor-pointer ml-1"
        >
            Clear all
        </button>
    </div>
@endif
