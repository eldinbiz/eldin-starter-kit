@props(['datatable'])

{{-- Flash Messages --}}
@if (session('success'))
    <div class="mb-4 rounded-lg bg-success-50 p-4 text-sm text-success-600 dark:bg-success-500/10 dark:text-success-500">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4 rounded-lg bg-error-50 p-4 text-sm text-error-600 dark:bg-error-500/10 dark:text-error-400">
        {{ session('error') }}
    </div>
@endif

{{-- Filters Panel --}}
<x-data-table.filters :datatable="$datatable" />

{{-- Active Filter Chips --}}
<x-data-table.filter-chips :datatable="$datatable" />

@if($datatable->results()->isEmpty())
    <div class="py-10 text-center">
        <p class="text-gray-500 dark:text-gray-400">No results match your filters.</p>
        <a href="{{ $datatable->clearFiltersUrl() }}" class="text-brand-500 hover:text-brand-600 text-sm font-medium mt-1 inline-block">
            Clear filters
        </a>
    </div>
@else
    {{-- Desktop Table View --}}
    <div class="hidden md:block w-full overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-gray-100 border-y dark:border-gray-800">
                    @foreach($datatable->columns() as $column)
                        <x-data-table.th :column="$column" :datatable="$datatable" />
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($datatable->results() as $row)
                    {!! $datatable->renderRow($row) !!}
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile Card View --}}
    <div class="block md:hidden">
        <div class="grid grid-cols-1 gap-4">
            @foreach($datatable->results() as $row)
                {!! $datatable->renderMobileRow($row) !!}
            @endforeach
        </div>
    </div>

    {{-- Pagination --}}
    <x-data-table.pagination :datatable="$datatable" />
@endif
