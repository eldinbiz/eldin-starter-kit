@props(['datatable'])

@php
    /** @var \App\DataTables\DataTable $datatable */
@endphp

@if($datatable->hasActiveFilters())
    <div class="mt-3 flex flex-wrap items-center gap-2">
        <span class="text-xs text-gray-500 dark:text-gray-400">Active filters:</span>

        {{-- Search chip --}}
        @if($datatable->search() !== '' && $datatable->search() !== '0')
            <a href="{{ $datatable->filterDismissUrl('search') }}" class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-600 hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400 dark:hover:bg-brand-500/25">
                "{{ $datatable->search() }}" ✕
            </a>
        @endif

        {{-- Filter chips from each visible filter --}}
        @foreach($datatable->filters() as $filter)
            @php
                $value = $datatable->activeFilterValues()[$filter->name] ?? null;
            @endphp

            @if($filter->isVisible() && $filter->hasValue($value))
                @if($filter->type === 'checkbox' && is_array($value))
                    @foreach($value as $singleValue)
                        <a href="{{ $datatable->filterDismissUrl($filter->name, $singleValue) }}" class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-600 hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400 dark:hover:bg-brand-500/25">
                            {{ $datatable->filterValueLabel($filter, $singleValue) }} ✕
                        </a>
                    @endforeach
                @else
                    <a href="{{ $datatable->filterDismissUrl($filter->name) }}" class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-600 hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400 dark:hover:bg-brand-500/25">
                        {{ $datatable->filterValueLabel($filter, $value) }} ✕
                    </a>
                @endif
            @endif
        @endforeach

        <a href="{{ $datatable->clearFiltersUrl() }}" class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 underline">
            Clear all filters
        </a>
    </div>
@endif
