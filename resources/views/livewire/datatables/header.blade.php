@props([
    'placeholder' => 'Search records...',
    'hasFilters' => true,
])

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-4">
    {{-- Search Input & Filter Toggle --}}
    <div class="flex flex-1 items-center gap-3">
        {{-- Search Input --}}
        <div class="relative flex-1 max-w-md">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400 dark:text-gray-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </span>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ $placeholder }}"
                class="h-10 w-full rounded-lg border border-gray-300 bg-white pl-10 pr-9 text-sm text-gray-800 placeholder-gray-400 focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
            />
            @if ($this->search !== '')
                <button
                    type="button"
                    wire:click="$set('search', '')"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-pointer"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </button>
            @endif
        </div>

        {{-- Filter Drawer Toggle --}}
        @if ($hasFilters)
            <button
                type="button"
                @click="typeof filterPanelOpen !== 'undefined' ? filterPanelOpen = !filterPanelOpen : null"
                :class="{
                    'border-brand-500 bg-brand-50 text-brand-600 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-400': (typeof filterPanelOpen !== 'undefined' && filterPanelOpen) || {{ $this->activeFilterCount() > 0 ? 'true' : 'false' }},
                    'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700': !(typeof filterPanelOpen !== 'undefined' && filterPanelOpen) && {{ $this->activeFilterCount() === 0 ? 'true' : 'false' }}
                }"
                class="inline-flex items-center gap-2 h-10 px-3.5 rounded-lg border text-sm font-medium transition cursor-pointer"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                </svg>
                <span>Filters</span>
                @if ($this->activeFilterCount() > 0)
                    <span class="inline-flex items-center justify-center h-5 min-w-[20px] px-1 rounded-full text-xs font-semibold bg-brand-500 text-white">
                        {{ $this->activeFilterCount() }}
                    </span>
                @endif
            </button>
        @endif
    </div>

    {{-- Per Page & Extra Actions --}}
    <div class="flex items-center justify-end gap-3">
        {{-- Per Page Selector --}}
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <label for="per_page_select" class="hidden sm:inline">Per page:</label>
            <select
                id="per_page_select"
                wire:model.live="perPage"
                class="h-10 rounded-lg border border-gray-300 bg-white px-2.5 text-sm text-gray-700 focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
            >
                @foreach ($this->perPageOptions as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
        </div>

        {{-- Action Slot (e.g. Create Button) --}}
        {{ $actions ?? '' }}
    </div>
</div>
