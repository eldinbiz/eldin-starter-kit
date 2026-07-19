@props(['datatable'])

@php
    /** @var \App\DataTables\DataTable $datatable */
    $visibleFilters = array_filter($datatable->filters(), fn ($f) => $f->isVisible());
@endphp

@if(!empty($visibleFilters))
    <div x-data="{ filtersOpen: {{ $datatable->hasActiveFilters() ? 'true' : 'false' }} }" class="mb-6">
        <button
            @click="filtersOpen = ! filtersOpen"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
        >
            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M2.29004 5.90393H17.7067" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M17.7075 14.0961H2.29085" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12.0826 3.33331C13.5024 3.33331 14.6534 4.48431 14.6534 5.90414C14.6534 7.32398 13.5024 8.47498 12.0826 8.47498C10.6627 8.47498 9.51172 7.32398 9.51172 5.90415C9.51172 4.48432 10.6627 3.33331 12.0826 3.33331Z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
                <path d="M7.91745 11.525C6.49762 11.525 5.34662 12.676 5.34662 14.0959C5.34661 15.5157 6.49762 16.6667 7.91745 16.6667C9.33728 16.6667 10.4883 15.5157 10.4883 14.0959C10.4883 12.676 9.33728 11.525 7.91745 11.525Z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
            </svg>
            Advanced Filters
            <svg class="fill-current transition-transform" :class="filtersOpen ? 'rotate-180' : ''" width="16" height="16" viewBox="0 0 24 24"><path d="M12 16l-6-6h12z"/></svg>
        </button>

        <div x-show="filtersOpen" x-transition class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
            <form method="GET" action="{{ url()->current() }}">
                {{-- Preserve sort/direction so filters don't reset sorting --}}
                <input type="hidden" name="sort" value="{{ $datatable->sort() }}">
                <input type="hidden" name="direction" value="{{ $datatable->direction() }}">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($visibleFilters as $filter)
                        @if($filter->type === 'text')
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $filter->label }}</label>
                                <input
                                    type="text"
                                    name="{{ $filter->name }}"
                                    value="{{ request()->string($filter->name)->toString() }}"
                                    placeholder="{{ $filter->placeholder }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>
                        @elseif($filter->type === 'select')
                            @php $options = $filter->getOptions(); @endphp
                            @if(!empty($options))
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $filter->label }}</label>
                                    <select
                                        name="{{ $filter->name }}"
                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                    >
                                        <option value="">All</option>
                                        @foreach($options as $value => $label)
                                            <option value="{{ $value }}" {{ request()->input($filter->name) == (string) $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        @elseif($filter->type === 'checkbox')
                            @php $options = $filter->getOptions(); @endphp
                            @if(!empty($options))
                                <div x-data="{ open: false }" class="relative">
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $filter->label }}</label>
                                    <button
                                        type="button"
                                        @click="open = ! open"
                                        class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-left text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                    >
                                        @php
                                            $selectedValues = (array) request()->input($filter->name, []);
                                        @endphp
                                        <span x-text="open ? 'Select {{ strtolower($filter->label) }}...' : '{{ !empty($selectedValues) ? count($selectedValues).' selected' : 'All '.strtolower($filter->label) }}'"></span>
                                    </button>
                                    <div x-show="open" @click.outside="open = false" class="absolute z-10 mt-1 w-full rounded-lg border border-gray-200 bg-white p-2 shadow-lg dark:border-gray-800 dark:bg-gray-900">
                                        @foreach($options as $value => $label)
                                            <label class="flex items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    name="{{ $filter->name }}[]"
                                                    value="{{ $value }}"
                                                    {{ in_array($value, $selectedValues) ? 'checked' : '' }}
                                                    class="rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                                >
                                                <span class="text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @elseif($filter->type === 'date')
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $filter->label }}</label>
                                <input
                                    type="date"
                                    name="{{ $filter->name }}"
                                    value="{{ request()->string($filter->name)->toString() }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden"
                    >
                        Apply Filters
                    </button>
                    <a
                        href="{{ $datatable->clearFiltersUrl() }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
                    >
                        Clear All
                    </a>
                </div>
            </form>
        </div>
    </div>
@endif
